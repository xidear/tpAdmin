<?php
namespace app\job;

use app\model\SystemLog;
use think\queue\Job;

class LogRecordJob
{
    /**
     * 执行任务
     * @param Job $job
     * @param array $data 日志数据
     */
    public function fire(Job $job, array $data): void
    {
        try {
            // 尝试写入日志
            $result = SystemLog::create($data);

            // 任务执行成功，删除任务
            if (!$result->isEmpty()) {
                $job->delete();
                return;
            }
        } catch (\Exception $e) {
            // 记录错误日志
            trace('日志记录失败: ' . $e->getMessage(), 'error');
        }

        // 如果任务执行失败，检查重试次数
        if ($job->attempts() > 3) {
            $job->delete();
            trace('日志记录任务重试次数超限: ' . json_encode($data), 'error');
        }
    }
}
