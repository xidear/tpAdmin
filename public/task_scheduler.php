<?php
// 加载TP框架环境
require __DIR__ . '/../thinkphp/base.php';

use app\model\Task;
use app\service\TaskService;
use think\facade\Log;

// 调度器主逻辑
try {
    // 1. 获取当前时间（时区需与数据库一致，建议UTC或服务器时区）
    $currentTime = date('Y-m-d H:i:s');

    // 2. 查询“已启用”且“下次执行时间<=当前时间”的任务
    $tasks = Task::where([
        ['status', '=', Task::STATUS_ENABLED],
        ['next_exec_time', '<=', $currentTime]
    ])->select();

    if ($tasks->isEmpty()) {
        Log::info("【调度器】无到期任务，当前时间：{$currentTime}");
        exit;
    }

    // 3. 遍历任务并执行（多进程执行，避免单任务阻塞）
    foreach ($tasks as $task) {
        // 用进程池或多进程扩展（如Swoole）执行，避免单任务超时阻塞整体调度
        $pid = pcntl_fork();
        if ($pid == -1) {
            throw new Exception("创建进程失败");
        } elseif ($pid == 0) {
            // 子进程：执行任务
            try {
                $service = new TaskService();
                $result = $service->executeTask($task);
                Log::info("【任务执行】ID:{$task->id}，结果：" . ($result['success'] ? '成功' : '失败'));
            } catch (Exception $e) {
                Log::error("【任务执行异常】ID:{$task->id}，错误：{$e->getMessage()}");
            }
            exit(); // 子进程执行完退出
        }
    }

    // 4. 等待所有子进程结束（可选，根据需求是否阻塞）
    while (pcntl_waitpid(0, $status) != -1) {
        $status = pcntl_wexitstatus($status);
    }

} catch (Exception $e) {
    Log::error("【调度器异常】：{$e->getMessage()}");
}


//用法# 每分钟执行一次应用调度器
//* * * * * /usr/bin/php /var/www/task-system/public/task_scheduler.php >> /var/www/task-system/runtime/logs/task_scheduler.log 2>&1