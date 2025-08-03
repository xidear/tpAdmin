<?php
namespace app\queue\worker;

use think\queue\Worker;
use app\model\Task;
use app\service\TaskService;
use think\facade\Log;
use Swoole\Coroutine; // 若用 Swoole 环境，可借助协程

class TaskWorker extends Worker
{
    // 定时检查间隔（秒），建议 1-5 秒
    protected $checkInterval = 1;

    // 上次检查时间，避免频繁查询数据库
    protected $lastCheckTime = 0;

    /**
     * 重写 run 方法，在队列循环中加入定时任务检查
     */
    public function run()
    {
        // 启动队列 Worker 原生逻辑
        parent::run();

        // 启动后立即执行一次检查
        $this->checkAndExecuteTasks();
    }

    /**
     * 重写 process 方法，在每次处理队列任务后检查定时任务
     */
    protected function process($job, $data)
    {
        // 先处理队列任务
        parent::process($job, $data);

        // 检查是否到达定时任务检查时间
        $now = time();
        if ($now - $this->lastCheckTime >= $this->checkInterval) {
            $this->checkAndExecuteTasks();
            $this->lastCheckTime = $now;
        }
    }

    /**
     * 检查并执行到期任务
     */
    protected function checkAndExecuteTasks()
    {
        // 多进程/协程环境下，加锁避免重复执行（关键！）
        $lockKey = 'task_scheduler_lock';
        $lock = cache()->lock($lockKey, 5); // 锁超时 5 秒
        if (!$lock->acquire()) {
            return; // 已有进程在执行，直接返回
        }

        try {
            $currentTime = date('Y-m-d H:i:s');
            // 查询符合条件的任务（已启用、到执行时间、匹配当前平台）
            $tasks = Task::where([
                ['status', '=', Task::STATUS_ENABLED],
                ['next_exec_time', '<=', $currentTime],
                ['platform', 'in', [0, PHP_OS === 'Linux' ? 1 : 2]]
            ])->select();

            if ($tasks->isEmpty()) {
                return;
            }

            // 遍历执行任务（放入队列异步执行，不阻塞当前进程）
            foreach ($tasks as $task) {
                // 用队列异步执行，避免任务执行时间过长阻塞调度
                \think\facade\Queue::push(\app\queue\job\ExecuteTask::class, [
                    'task_id' => $task->getKey()
                ], 'task_exec'); // 单独的任务执行队列

                Log::info("【定时任务】ID:{$task->getKey()} 已加入执行队列");
            }
        } catch (\Exception $e) {
            Log::error("【定时任务检查异常】：{$e->getMessage()}");
        } finally {
            $lock->release(); // 释放锁
        }
    }
}


//启动方法# Linux
//php think queue:work --worker=app\queue\worker\TaskWorker --queue=default,task_exec -d

# Windows（需用 cmd 或 PowerShell，不支持 -d 后台运行，可配合 supervisor 或 pm2）
//php think queue:work --worker=app\queue\worker\TaskWorker --queue=default,task_exec