<?php
if (!extension_loaded('redis')) {
    die("Redis扩展未安装，请先安装Redis扩展\n");
}

// 检查Swoole扩展（如果需要协程功能）
if (!function_exists('go')) {
    // 如果没有Swoole，定义一个替代函数
    if (!function_exists('go')) {
        function go($callback)
        {
            // 使用Workerman的Timer替代Swoole的go协程
            Workerman\Timer::add(0.001, function () use ($callback) {
                call_user_func($callback);
            }, null, false);
        }
    }
}

use app\common\enum\Status;
use app\common\enum\TaskPlatform;
use app\common\enum\TaskExecuteMode;
use think\App;
use think\facade\Queue;
use app\common\service\TaskService;
use app\controller\admin\websocket\Message;
use app\model\Task;
use think\facade\Log;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Connection\TcpConnection;
use Workerman\Timer;
use Workerman\Worker;

// 1. 加载依赖与初始化框架
require_once __DIR__ . '/vendor/autoload.php';
const APP_PATH = __DIR__ . '/app/';
$app = new App();
$app->initialize();

// 在框架初始化后添加日志目录创建
$logDir = runtime_path() . 'log';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// 2. 判断操作系统（区分Windows/Linux）
$isWindows = str_contains(PHP_OS, 'WIN');

// 3. 创建WebSocket服务（Linux多进程，Windows单进程）
$ws_worker = new Worker("websocket://0.0.0.0:2346");
$ws_worker->count = $isWindows ? 1 : 4; // Linux设为4进程（根据CPU核心数调整）

// 4. 全局存储客户端连接（单进程有效，多进程需依赖Redis广播）
global $connections;
$connections = [];

// 5. 客户端连接/断开事件（维护连接列表）
$ws_worker->onConnect = function (TcpConnection $connection) {
    global $connections;
    $connections[$connection->id] = $connection;
    $connection->send(json_encode([
        'type' => 'system',
        'data' => '连接成功，等待消息'
    ]));
};
$ws_worker->onClose = function (TcpConnection $connection) {
    global $connections;
    unset($connections[$connection->id]);
};

// 6. 服务启动：初始化Redis订阅和定时任务调度
// 在onWorkerStart中添加队列处理
$ws_worker->onWorkerStart = function (Worker $worker) use ($isWindows) {
    global $connections;

    // 初始化定时推送（原有逻辑）
    Message::initTimer($worker);

    // === 新增：初始化定时任务调度器 ===
    initTaskScheduler($worker);

    // === 新增：初始化队列处理器 ===
    initQueueProcessor($worker);

    // Redis配置（从config/cache.php读取）
    $redisConfig = config('cache.stores.redis');
    $redisChannel = Message::$redisChannel;
    $redisHost = $redisConfig['host'];
    $redisPort = $redisConfig['port'];

    // 根据操作系统选择不同的Redis订阅方式（原有逻辑）
    if ($isWindows) {
        subscribeRedisOnWindows($redisHost, $redisPort, $redisChannel, $connections);
    } else {
        subscribeRedisOnLinux($redisHost, $redisPort, $redisChannel, $connections);
    }
};

/**
 * 初始化队列处理器
 */
function initQueueProcessor(Worker $worker): void
{
    // 只有WorkerId=0的进程处理队列（避免重复处理）
    if ($worker->id !== 0) {
        return;
    }

    // 设置队列处理间隔
    $queueInterval = 1; // 每5秒检查一次队列

    Timer::add($queueInterval, function () {
        processQueueJobs();
    });

    echo "队列处理器已启动，检查间隔：{$queueInterval}秒\n";
}

/**
 * 处理队列任务
 */
function processQueueJobs(): void
{
    try {
        // 获取队列连接
        $connection = Queue::connection();

        // 定义需要处理的队列列表
        $queues = ['default', 'region_cache', 'export', 'import'];

        foreach ($queues as $queueName) {
            // 移除手动触发过期任务迁移的代码
            // Redis队列会自动处理过期任务
            
            // 尝试获取下一个任务
            $job = $connection->pop($queueName);

            if ($job) {
                // 检查任务重试次数
                $attempts = $job->attempts();
                try {

                    $maxAttempts = 3;

                    if ($attempts > $maxAttempts) {
                        echo "队列任务超过最大重试次数，已删除 [队列: {$queueName}, 重试次数: {$attempts}]\n";
                        Log::info("队列任务超过最大重试次数，已删除", ['queue' => $queueName, 'attempts' => $attempts, 'job' => get_class($job)]);
                        $job->delete();
                        continue;
                    }

                    // 处理任务
                    $job->fire();
                    Log::info("队列任务处理成功", ['queue' => $queueName, 'attempts' => $attempts, 'job' => get_class($job)]);
                } catch (Exception $e) {
                    echo "队列任务处理失败，将在10秒后重试 [队列: {$queueName}, 重试次数: {$attempts}]\n";
                    Log::error("队列任务处理失败：" . $e->getMessage(), ['queue' => $queueName, 'attempts' => $attempts, 'job' => get_class($job)]);

                    // 检查任务是否已经被释放或删除
                    if (!$job->isDeletedOrReleased()) {
                        // 任务处理失败，重新入队
                        $job->release(10); // 10秒后重试
                    }
                }
            }
        }
    } catch (Exception $e) {
        Log::error("队列处理失败：" . $e->getMessage(), ['exception' => get_class($e)]);
    }
}

// === 新增：定时任务调度核心逻辑 ===
function initTaskScheduler(Worker $worker): void
{
    // 只有WorkerId=0的进程执行调度（避免多进程重复检查）
    if ($worker->id !== 0) {
        return;
    }
    
    // 获取调度模式配置
    $schedulerConfig = config('task_scheduler');
    $schedulerMode = $schedulerConfig['scheduler_mode'] ?? 'polling';
    
    if ($schedulerMode === 'precise') {
        // 精确定时器模式
        initPreciseScheduler($worker, $schedulerConfig['precise']);
    } else {
        // 轮询模式（默认）
        initPollingScheduler($worker, $schedulerConfig['polling']);
    }
}

// 轮询模式初始化
function initPollingScheduler(Worker $worker, array $config): void
{
    $checkInterval = $config['check_interval'] ?? 10;
    
    // 添加定时器
    Timer::add($checkInterval, function () {
        checkAndExecuteTasks();
    });
    
    echo "定时任务调度器已启动（轮询模式），检查间隔：{$checkInterval}秒\n";
}

// 精确定时器模式初始化
function initPreciseScheduler(Worker $worker, array $config): void
{
    $refreshInterval = $config['refresh_interval'] ?? 60;
    $maxTasks = $config['max_tasks'] ?? 1000;
    
    // 存储任务定时器和哈希值的全局变量
    global $taskTimers, $lastTaskHash;
    $taskTimers = [];
    $lastTaskHash = null;
    
    // 初始化时加载所有任务
    loadAllTasksForPreciseScheduler($maxTasks);
    
    // 定期检查任务变化（通过哈希值）
    Timer::add($refreshInterval, function () use ($maxTasks) {
        checkAndReloadTasksForPreciseScheduler($maxTasks);
    });
    
    echo "定时任务调度器已启动（精确定时器模式），刷新间隔：{$refreshInterval}秒\n";
}

// 数据库直接计算哈希值
function getTasksHashFromDatabase(): string
{
    try {
        $currentPlatform = PHP_OS === 'Linux' ? TaskPlatform::LINUX->value : TaskPlatform::WINDOWS->value;
        
        // 使用数据库函数直接计算哈希值
        $result = Task::where('status', Status::Normal->value)
            ->where(function($query) use ($currentPlatform) {
                $query->where('platform', TaskPlatform::ALL->value)
                    ->whereOr('platform', $currentPlatform);
            })
            ->field([
                'MD5(GROUP_CONCAT(CONCAT(id, "|", name, "|", execute_mode, "|", COALESCE(schedule, ""), "|", COALESCE(execute_at, ""), "|", UNIX_TIMESTAMP(updated_at)) ORDER BY id SEPARATOR "||")) as task_hash'
            ])
            ->find();
        
        return $result['task_hash'] ?? md5('');
        
    } catch (Exception $e) {
        Log::error("【精确定时器】数据库计算哈希值失败：" . $e->getMessage());
        return 'error';
    }
}

// 检查任务变化并重新加载（基于数据库哈希值）
function checkAndReloadTasksForPreciseScheduler(int $maxTasks): void
{
    global $lastTaskHash;
    
    try {
        // 直接从数据库获取哈希值，不查询任务数据
        $currentHash = getTasksHashFromDatabase();
        
        // 如果哈希值没有变化，则不需要重新加载
        if ($currentHash === $lastTaskHash) {
            echo "【精确定时器】任务列表无变化，跳过重新加载\n";
            return;
        }
        
        echo "【精确定时器】检测到任务列表变化，哈希值：{$lastTaskHash} -> {$currentHash}\n";
        
        // 更新哈希值并重新加载任务
        $lastTaskHash = $currentHash;
        loadAllTasksForPreciseScheduler($maxTasks);
        
    } catch (Exception $e) {
        Log::error("【精确定时器】检查任务变化失败：" . $e->getMessage());
    }
}

// 加载所有任务到精确定时器
function loadAllTasksForPreciseScheduler(int $maxTasks): void
{
    global $taskTimers;
    
    // 清除现有定时器
    foreach ($taskTimers as $timerId) {
        Timer::del($timerId);
    }
    $taskTimers = [];
    
    try {
        $currentTime = date('Y-m-d H:i:s');
        $currentPlatform = PHP_OS === 'Linux' ? TaskPlatform::LINUX->value : TaskPlatform::WINDOWS->value;
        
        // 查询所有活跃任务
        $tasks = Task::where('status', Status::Normal->value)
            ->where(function($query) use ($currentPlatform) {
                $query->where('platform', TaskPlatform::ALL->value)
                    ->whereOr('platform', $currentPlatform);
            })
            ->limit($maxTasks)
            ->select();
        
        if ($tasks->isEmpty()) {
            echo "【精确定时器】没有找到活跃任务\n";
            return;
        }
        
        global $app;
        $service = new TaskService($app);
        
        foreach ($tasks as $task) {
            createTaskTimer($task, $service);
        }
        
        echo "【精确定时器】已加载 " . count($tasks) . " 个任务\n";
        
    } catch (Exception $e) {
        Log::error("【精确定时器】加载任务失败：" . $e->getMessage());
    }
}

// 为单个任务创建定时器
function createTaskTimer($task, $service): void
{
    global $taskTimers;
    
    $taskId = $task->getKey();
    
    // 清除该任务的旧定时器
    if (isset($taskTimers[$taskId])) {
        Timer::del($taskTimers[$taskId]);
        unset($taskTimers[$taskId]);
    }
    
    if ($task->execute_mode === TaskExecuteMode::LOOP->value) {
        // 循环任务：解析crontab表达式
        $cronExpression = $task->schedule;
        $nextExecTime = getNextExecTimeFromCron($cronExpression);
        
        if ($nextExecTime) {
            $delay = max(0, strtotime($nextExecTime) - time());
            
            $timerId = Timer::add($delay, function () use ($task, $service) {
                executeTaskWithPreciseTimer($task, $service);
                // 执行后重新设置下一次定时器
                createTaskTimer($task, $service);
            });
            
            $taskTimers[$taskId] = $timerId;
            echo "【精确定时器】任务 {$task->name} (ID:{$taskId}) 已调度，下次执行时间：{$nextExecTime}\n";
        }
    } else {
        // 一次性任务
        $executeAt = $task->execute_at;
        $delay = max(0, strtotime($executeAt) - time());
        
        if ($delay > 0) {
            $timerId = Timer::add($delay, function () use ($task, $service) {
                executeTaskWithPreciseTimer($task, $service);
            });
            
            $taskTimers[$taskId] = $timerId;
            echo "【精确定时器】一次性任务 {$task->name} (ID:{$taskId}) 已调度，执行时间：{$executeAt}\n";
        } else {
            // 如果执行时间已过，立即执行
            executeTaskWithPreciseTimer($task, $service);
        }
    }
}

// 使用精确定时器执行任务
function executeTaskWithPreciseTimer($task, $service): void
{
    global $taskTimers;
    
    $taskId = $task->getKey();
    
    Log::info("【精确定时器】开始执行任务 ID:{$taskId}，名称:{$task->name}");
    
    // 异步执行任务
    go(function () use ($service, $task, $taskId) {
        try {
            $result = $service->executeTask($task);
            if ($result['success']) {
                echo "【精确定时器】任务 ID:{$taskId} 执行成功\n";
                Log::info("【精确定时器】任务 ID:{$taskId} 执行成功");
            } else {
                echo "【精确定时器】任务 ID:{$taskId} 执行失败：{$result['message']}\n";
                Log::error("【精确定时器】任务 ID:{$taskId} 执行失败：{$result['message']}");
            }
        } catch (Exception $e) {
            echo "【精确定时器】任务 ID:{$taskId} 异常：{$e->getMessage()}\n";
            Log::error("【精确定时器】任务 ID:{$taskId} 异常：{$e->getMessage()}");
        }
        
        // 如果是一次性任务，从定时器列表中移除
        if ($task->execute_mode === TaskExecuteMode::ONCE->value) {
            global $taskTimers;
            if (isset($taskTimers[$taskId])) {
                unset($taskTimers[$taskId]);
            }
        }
    });
}

// 从crontab表达式计算下次执行时间
function getNextExecTimeFromCron(string $cronExpression): ?string
{
    try {
        // 简单的crontab解析实现
        // 这里可以使用更完整的crontab解析库，如dragonmantank/cron-expression
        $parts = explode(' ', trim($cronExpression));
        if (count($parts) !== 5) {
            return null;
        }
        
        list($minute, $hour, $day, $month, $weekday) = $parts;
        
        // 如果是通配符 * * * * *，则每分钟执行
        if ($minute === '*' && $hour === '*' && $day === '*' && $month === '*' && $weekday === '*') {
            return date('Y-m-d H:i:s', strtotime('+1 minute'));
        }
        
        // 这里可以添加更复杂的crontab解析逻辑
        // 为了演示，我们简化处理：如果包含通配符，则每分钟执行
        if (strpos($cronExpression, '*') !== false) {
            return date('Y-m-d H:i:s', strtotime('+1 minute'));
        }
        
        // 如果没有通配符，尝试解析为固定时间
        $timeStr = sprintf('%s %s %s %s %s', $minute, $hour, $day, $month, $weekday);
        $timestamp = strtotime($timeStr);
        
        if ($timestamp === false || $timestamp <= time()) {
            // 如果时间已过，设置为明天同一时间
            $timestamp = strtotime('+1 day ' . $timeStr);
        }
        
        return date('Y-m-d H:i:s', $timestamp);
        
    } catch (Exception $e) {
        Log::error("【精确定时器】解析crontab表达式失败：" . $e->getMessage(), ['expression' => $cronExpression]);
        return null;
    }
}

function checkAndExecuteTasks(): void
{
    // 1. 连接Redis（从config/cache.php读取配置）
    $redisConfig = config('cache.stores.redis');
    $redis = new Redis();
    try {
        $redis->connect($redisConfig['host'], $redisConfig['port']);
        if (!empty($redisConfig['password'])) {
            $redis->auth($redisConfig['password']);
        }
        if (isset($redisConfig['select'])) {
            $redis->select($redisConfig['select']);
        }
    } catch (RedisException $e) {
        Log::error("【定时任务】Redis连接失败：" . $e->getMessage());
        return;
    }

    // 2. 分布式锁参数
    $lockKey = 'task_scheduler_lock';
    $lockValue = uniqid();
    $lockExpireMs = 10000;

    // 3. 尝试获取锁
    try {
        $acquired = $redis->set($lockKey, $lockValue, ['NX', 'PX' => $lockExpireMs]);
    } catch (Exception $e) {
        Log::error("【定时任务】获取分布式锁失败：" . $e->getMessage());
        return;
    }

    if (!$acquired) {
        return;
    }

    try {
        $currentTime = date('Y-m-d H:i:s');
        $currentPlatform = PHP_OS === 'Linux' ? TaskPlatform::LINUX->value : TaskPlatform::WINDOWS->value;

        // 4. 查询符合条件的任务
        $tasks = Task::where('status', Status::Normal->value)
            ->where(function($query) use ($currentPlatform) {
                $query->where('platform', TaskPlatform::ALL->value)
                    ->whereOr('platform', $currentPlatform);
            })
            ->where(function($query) use ($currentTime) {
                // 循环执行任务和一次性执行任务并列
                $query->where(function($q) use ($currentTime) {
                    // 循环执行任务
                    $q->where('execute_mode', TaskExecuteMode::LOOP->value)
                      ->where('next_exec_time', '<=', $currentTime);
                })->whereOr(function($q) use ($currentTime) {
                    // 一次性执行任务
                    $q->where('execute_mode', TaskExecuteMode::ONCE->value)
                      ->where('execute_at', '<=', $currentTime);
                });
            })
            ->select();

          
        if ($tasks->isEmpty()) {
            return;
        }

        global $app;
        // 5. 执行任务
        $service = new TaskService($app);
        foreach ($tasks as $task) {

            Log::info("【定时任务】开始执行任务 ID:{\$task->getKey()}，名称:{\$task->name}");

            // 异步执行任务（使用兼容的go函数）
            go(function () use ($service, $task) {
                try {
                    $result = $service->executeTask($task);
                    if ($result['success']) {
                        echo "【定时任务】任务 ID:{$task->getKey()} 执行成功\n";

                        Log::info("【定时任务】任务 ID:{$task->getKey()} 执行成功");
                    } else {
                        echo "【定时任务】任务 ID:{$task->getKey()} 执行失败：{$result['message']}\n";

                        Log::error("【定时任务】任务 ID:{$task->getKey()} 执行失败：{$result['message']}");
                    }
                } catch (Exception $e) {
                    echo "【定时任务】任务 ID:{$task->getKey()} 异常：{$e->getMessage()}\n";

                    Log::error("【定时任务】任务 ID:{$task->getKey()} 异常：{$e->getMessage()}");
                }
            });
        }

    } catch (Exception $e) {
        Log::error("【定时任务】检查逻辑异常：{$e->getMessage()}");
    } finally {
        // 6. 释放锁
        $releaseScript = <<<LUA
if redis.call('get', KEYS[1]) == ARGV[1] then
    return redis.call('del', KEYS[1])
else
    return 0
end
LUA;
        try {
            $redis->eval($releaseScript, [$lockKey, $lockValue], 1);
        } catch (Exception $e) {
            Log::error("【定时任务】释放分布式锁失败：" . $e->getMessage());
        }
    }
}

// 7. Windows下的Redis订阅（非阻塞TCP）
function subscribeRedisOnWindows($host, $port, $channel, &$connections): void
{
    $conn = new AsyncTcpConnection("tcp://{$host}:{$port}");

    // 连接成功后发送订阅命令
    $conn->onConnect = function ($tcp_conn) use ($channel) {
        echo "Windows环境：Redis TCP连接成功，订阅频道 {$channel}\n";
        $tcp_conn->send("SUBSCRIBE {$channel}\r\n");
    };

    // 解析Redis消息并推送
    $conn->onMessage = function ($tcp_conn, $data) use (&$connections) {
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
    $conn->onClose = function ($tcp_conn) {
        echo "Windows环境：Redis连接断开，3秒后重连...\n";
        Timer::add(3, function () use ($tcp_conn) {
            $tcp_conn->reconnect();
        }, null, false);
    };

    $conn->connect();
}

// 8. Linux下的Redis订阅（PHP Redis扩展）
function subscribeRedisOnLinux($host, $port, $channel, &$connections): void
{
    try {
        $redis = new Redis();
        $redis->connect($host, $port);
        $redis->setOption(Redis::OPT_READ_TIMEOUT, -1); // 关闭超时
        echo "Linux环境：Redis扩展连接成功，订阅频道 {$channel}\n";

        // 订阅消息并推送
        $redis->subscribe([$channel], function ($redis, $chan, $message) use (&$connections) {
            foreach ($connections as $ws_conn) {
                if ($ws_conn->getStatus() === TcpConnection::STATUS_ESTABLISHED) {
                    $ws_conn->send($message);
                }
            }
        });
    } catch (Exception $e) {
        echo "Linux环境：Redis错误：{$e->getMessage()}，3秒后重连...\n";
        Timer::add(3, function () use ($host, $port, $channel, &$connections) {
            subscribeRedisOnLinux($host, $port, $channel, $connections);
        }, null, false);
    }
}

// 9. 运行服务
Worker::runAll();