<?php
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\AsyncTcpConnection;
use think\facade\Cache;
use app\controller\admin\websocket\Message;

// 1. 加载依赖与初始化框架
require_once __DIR__ . '/vendor/autoload.php';
define('APP_PATH', __DIR__ . '/app/');
$app = new \think\App();
$app->initialize();

// 2. 判断操作系统（区分Windows/Linux）
$isWindows = strpos(PHP_OS, 'WIN') !== false;

// 3. 创建WebSocket服务（Linux多进程，Windows单进程）
$ws_worker = new Worker("websocket://0.0.0.0:2346");
$ws_worker->count = $isWindows ? 1 : 4; // Linux设为4进程（根据CPU核心数调整）

// 4. 全局存储客户端连接（单进程有效，多进程需依赖Redis广播）
global $connections;
$connections = [];

// 5. 客户端连接/断开事件（维护连接列表）
$ws_worker->onConnect = function(TcpConnection $connection) {
    global $connections;
    $connections[$connection->id] = $connection;
    $connection->send(json_encode([
        'type' => 'system',
        'data' => '连接成功，等待消息'
    ]));
};
$ws_worker->onClose = function(TcpConnection $connection) {
    global $connections;
    unset($connections[$connection->id]);
};

// 6. 服务启动：初始化Redis订阅（区分Windows/Linux）
$ws_worker->onWorkerStart = function(Worker $worker) use ($isWindows) {
    global $connections;

    // 初始化定时推送（通用逻辑）
    Message::initTimer($worker);

    // Redis配置（统一频道）
    $redisChannel = Message::$redisChannel;
    $redisHost = '127.0.0.1';
    $redisPort = 6379;

    // === 根据操作系统选择不同的Redis订阅方式 ===
    if ($isWindows) {
        // Windows：用非阻塞TCP连接订阅（规避扩展问题）
        subscribeRedisOnWindows($redisHost, $redisPort, $redisChannel, $connections);
    } else {
        // Linux：用PHP Redis扩展订阅（支持多进程）
        subscribeRedisOnLinux($redisHost, $redisPort, $redisChannel, $connections);
    }
};

// 7. Windows下的Redis订阅（非阻塞TCP）
function subscribeRedisOnWindows($host, $port, $channel, &$connections): void
{
    $conn = new AsyncTcpConnection("tcp://{$host}:{$port}");

    // 连接成功后发送订阅命令
    $conn->onConnect = function($tcp_conn) use ($channel) {
        echo "Windows环境：Redis TCP连接成功，订阅频道 {$channel}\n";
        $tcp_conn->send("SUBSCRIBE {$channel}\r\n");
    };

    // 解析Redis消息并推送
    $conn->onMessage = function($tcp_conn, $data) use (&$connections) {
        $parts = explode("\r\n", trim($data));
        if (count($parts) >= 4 && $parts[0] === 'message') {
            $message = $parts[3];
            foreach ($connections as $ws_conn) {
                if ($ws_conn->getStatus() === TcpConnection::STATUS_ESTABLISHED) {
                    $ws_conn->send($message);
                }
            }
        }
    };

    // 断开重连
    $conn->onClose = function($tcp_conn) {
        echo "Windows环境：Redis连接断开，3秒后重连...\n";
        \Workerman\Timer::add(3, function() use ($tcp_conn) {
            $tcp_conn->reconnect();
        }, null, false);
    };

    $conn->connect();
}

// 8. Linux下的Redis订阅（PHP Redis扩展）
function subscribeRedisOnLinux($host, $port, $channel, &$connections): void
{
    try {
        $redis = new \Redis();
        $redis->connect($host, $port);
        $redis->setOption(\Redis::OPT_READ_TIMEOUT, -1); // 关闭超时
        echo "Linux环境：Redis扩展连接成功，订阅频道 {$channel}\n";

        // 订阅消息并推送
        $redis->subscribe([$channel], function($redis, $chan, $message) use (&$connections) {
            foreach ($connections as $ws_conn) {
                if ($ws_conn->getStatus() === TcpConnection::STATUS_ESTABLISHED) {
                    $ws_conn->send($message);
                }
            }
        });
    } catch (\Exception $e) {
        echo "Linux环境：Redis错误：{$e->getMessage()}，3秒后重连...\n";
        \Workerman\Timer::add(3, function() use ($host, $port, $channel, &$connections) {
            subscribeRedisOnLinux($host, $port, $channel, $connections);
        }, null, false);
    }
}

// 9. 运行服务
Worker::runAll();