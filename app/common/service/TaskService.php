<?php

namespace app\service;

use app\common\enum\SuccessOrFail;
use app\common\enum\TaskPlatform;
use app\common\enum\TaskType;
use app\common\enum\Status;
use app\model\Task;
use app\model\TaskLog;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\facade\Request;
use app\common\BaseService;

class TaskService extends BaseService
{
    /**
     * 执行任务
     * @param Task $task
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function executeTask(Task $task): array
    {
        // 记录任务开始日志
        $logId = TaskLog::recordStart(
            $task->id,
            $task->name,
            getmypid(),
            Request::ip()
        );

        try {
            // 检查平台兼容性
            $this->checkPlatformCompatibility($task);

            // 根据任务类型执行
            $output = match ($task->type) {
                TaskType::COMMAND->value => $this->executeCommand($task),
                TaskType::URL->value => $this->executeUrlRequest($task),
                TaskType::PHP->value => $this->executePhpMethod($task),
                default => throw new Exception("不支持的任务类型: {$task->type}"),
            };

            // 更新任务执行时间
            $task->last_exec_time = date('Y-m-d H:i:s');
            $task->next_exec_time = $this->calculateNextExecTime($task->schedule);
            $task->save();

            // 记录成功日志
            TaskLog::recordEnd(
                $logId,
                SuccessOrFail::Success->value,
                $output
            );

            return $this->success([
                'log_id' => $logId,
                'output' => $output
            ], '任务执行成功');
        } catch (\Exception $e) {
            // 记录失败日志
            TaskLog::recordEnd(
                $logId,
                SuccessOrFail::Fail->value,
                '',
                $e->getMessage()
            );

            // 处理重试逻辑
            if ($task->retry > 0) {
                $this->handleRetry($task, $e->getMessage());
            }

            $this->logInfo("任务执行失败 [ID:{$task->id}]", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error($e->getMessage(), [
                'log_id' => $logId
            ]);
        }
    }

    /**
     * 检查平台兼容性
     * @param Task $task
     * @throws \Exception
     */
    private function checkPlatformCompatibility(Task $task): void
    {
        // 根据当前系统获取对应的平台枚举值
        $currentPlatform = PHP_OS === 'Linux'
            ? TaskPlatform::LINUX->value
            : TaskPlatform::WINDOWS->value;

        // 检查任务平台是否为"全部"或当前平台
        if ($task->platform != TaskPlatform::ALL->value && $task->platform != $currentPlatform) {
            throw new Exception("任务不支持当前平台执行");
        }
    }

    /**
     * 执行命令行任务
     * @param Task $task
     * @return string
     * @throws \Exception
     */
    private function executeCommand(Task $task): string
    {
        $command = $task->content;

        // Linux平台下处理执行用户
        if (PHP_OS === 'Linux' && !empty($task->exec_user)) {
            $command = "sudo -u {$task->exec_user} {$command}";
        }

        // 执行命令并获取输出
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new Exception("命令执行失败: " . implode(PHP_EOL, $output));
        }

        return implode(PHP_EOL, $output);
    }

    /**
     * 执行URL请求任务
     * @param Task $task
     * @return string
     * @throws \Exception
     */
    private function executeUrlRequest(Task $task): string
    {
        $client = $this->make(\GuzzleHttp\Client::class);
        $url = $task->content;

        try {
            $response = $client->request('GET', $url, [
                'timeout' => $task->timeout
            ]);

            if ($response->getStatusCode() != 200) {
                throw new Exception("URL请求失败，状态码: {$response->getStatusCode()}");
            }

            return $response->getBody()->getContents();
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            throw new Exception("URL请求异常: {$e->getMessage()}");
        }
    }

    /**
     * 执行PHP方法任务
     * @param Task $task
     * @return string
     * @throws \Exception
     */
    private function executePhpMethod(Task $task): string
    {
        // 格式: App\Service\DemoService@methodName
        list($className, $methodName) = explode('@', $task->content);

        if (!class_exists($className)) {
            throw new Exception("类不存在: {$className}");
        }

        $instance = $this->make($className);

        if (!method_exists($instance, $methodName)) {
            throw new Exception("方法不存在: {$methodName}");
        }

        // 执行方法并捕获输出
        ob_start();
        $result = $instance->$methodName();
        $output = ob_get_clean();

        return $output . (is_string($result) ? $result : var_export($result, true));
    }

    /**
     * 处理任务重试
     * @param Task $task
     * @param string $errorMsg
     */
    private function handleRetry(Task $task, string $errorMsg): void
    {
        // 这里实现重试逻辑，可以是延时队列或定时重试
        $this->logInfo("任务将进行重试 [ID:{$task->id}]", [
            'error' => $errorMsg,
            'retry_count' => $task->retry,
            'interval' => $task->interval
        ]);
    }

    /**
     * 计算下次执行时间
     * @param string $cronExpression
     * @return string
     */
    private function calculateNextExecTime(string $cronExpression): string
    {
        // 使用crontab解析库计算下次执行时间
        $parser = $this->make(\Cron\CronExpression::class, [$cronExpression]);
        return $parser->getNextRunDate()->format('Y-m-d H:i:s');
    }
}