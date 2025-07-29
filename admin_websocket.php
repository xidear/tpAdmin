<?php
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Timer;
use think\facade\Cache;
use think\facade\Log;
use app\controller\admin\websocket\Message;
use app\model\Task;
use app\service\TaskService;

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

// 6. 服务启动：初始化Redis订阅和定时任务调度
$ws_worker->onWorkerStart = function(Worker $worker) use ($isWindows) {
    global $connections;

    // 初始化定时推送（原有逻辑）
    Message::initTimer($worker);

    // === 新增：初始化定时任务调度器 ===
    initTaskScheduler($worker);

    // Redis配置（统一频道）
    $redisChannel = Message::$redisChannel;
    $redisHost = '127.0.0.1';
    $redisPort = 6379;

    // 根据操作系统选择不同的Redis订阅方式（原有逻辑）
    if ($isWindows) {
        subscribeRedisOnWindows($redisHost, $redisPort, $redisChannel, $connections);
    } else {
        subscribeRedisOnLinux($redisHost, $redisPort, $redisChannel, $connections);
    }
};

// === 新增：定时任务调度核心逻辑 ===
function initTaskScheduler(Worker $worker) {
    // 定时检查间隔（秒），根据精度需求调整
    $checkInterval = 10;

    // 只有WorkerId=0的进程执行调度（避免多进程重复检查）
    if ($worker->id !== 0) {
        return;
    }

    // 添加定时器
    Timer::add($checkInterval, function() {
        checkAndExecuteTasks();
    });

    echo "定时任务调度器已启动，检查间隔：{$checkInterval}秒\n";
}

function checkAndExecuteTasks(): void
{
    // 1. 连接Redis（直接使用Redis扩展，不通过Cache门面）
    $redis = new \Redis();
    try {
        $redis->connect('127.0.0.1', 6379);
    } catch (\Exception $e) {
        Log::error("【定时任务】Redis连接失败：{$e->getMessage()}");
        return;
    }

    // 2. 分布式锁参数
    $lockKey = 'task_scheduler_lock';
    $lockValue = uniqid(); // 唯一值，用于验证锁的持有者（防止误释放）
    $lockExpireMs = 10000; // 锁超时时间：10秒（避免死锁）

    // 3. 尝试获取锁：SET key value NX PX 超时时间
    // NX：仅当key不存在时才设置；PX：指定过期时间（毫秒）
    try {
        $acquired = $redis->set($lockKey, $lockValue, ['NX', 'PX' => $lockExpireMs]);
    } catch (RedisException $e) {
    }

    if (!$acquired) {
        Log::info("【定时任务】其他进程正在执行检查，本次跳过");
        return;
    }

    try {
        $currentTime = date('Y-m-d H:i:s');
        $currentPlatform = PHP_OS === 'Linux' ? \app\common\enum\TaskPlatform::LINUX->value : \app\common\enum\TaskPlatform::WINDOWS->value;

        // 4. 查询符合条件的任务（原有逻辑不变）
        $tasks = Task::where([
            ['status', '=', \app\common\enum\Status::Normal],
            ['next_exec_time', '<=', $currentTime],
            ['platform', 'in', [\app\common\enum\TaskPlatform::ALL->value, $currentPlatform]]
        ])->select();

        if ($tasks->isEmpty()) {
            Log::info("【定时任务】无到期任务，当前时间：{$currentTime}");
            return;
        }

        global $app;
        // 5. 执行任务（原有逻辑不变）
        $service = new TaskService($app);
        foreach ($tasks as $task) {
            Log::info("【定时任务】开始执行任务 ID:{$task->id}，名称:{$task->name}");

            // 异步执行任务（不阻塞当前定时器）
            go(function() use ($service, $task) {
                try {
                    $result = $service->executeTask($task);
                    if ($result['success']) {
                        Log::info("【定时任务】任务 ID:{$task->id} 执行成功");
                    } else {
                        Log::error("【定时任务】任务 ID:{$task->id} 执行失败：{$result['message']}");
                    }
                } catch (\Exception $e) {
                    Log::error("【定时任务】任务 ID:{$task->id} 异常：{$e->getMessage()}");
                }
            });
        }

    } catch (\Exception $e) {
        Log::error("【定时任务】检查逻辑异常：{$e->getMessage()}");
    } finally {
        // 6. 释放锁：用Lua脚本保证原子性（防止释放非自己持有的锁）
        $releaseScript = <<<LUA
if redis.call('get', KEYS[1]) == ARGV[1] then
    return redis.call('del', KEYS[1])
else
    return 0
end
LUA;
        // 执行脚本：KEYS[1] = 锁键，ARGV[1] = 锁值
        $redis->eval($releaseScript, [$lockKey, $lockValue], 1);
    }
}
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
        Timer::add(3, function() use ($tcp_conn) {
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
        Timer::add(3, function() use ($host, $port, $channel, &$connections) {
            subscribeRedisOnLinux($host, $port, $channel, $connections);
        }, null, false);
    }
}

// 9. 运行服务
Worker::runAll();