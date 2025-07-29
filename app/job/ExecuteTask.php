<?php
namespace app\queue\job;

use think\queue\Job;
use app\model\Task;
use app\service\TaskService;

class ExecuteTask
{
    public function fire(Job $job, $data)
    {
        $taskId = $data['task_id'];
        $task = Task::find($taskId);

        if (!$task || $task->status != Task::STATUS_ENABLED) {
            $job->delete();
            return;
        }

        // 调用服务执行任务
        $service = new TaskService();
        $result = $service->executeTask($task);

        if ($result['success']) {
            $job->delete(); // 执行成功，删除队列任务
        } elseif ($job->attempts() >= 3) {
            $job->delete(); // 重试3次失败，放弃
        }
    }
}