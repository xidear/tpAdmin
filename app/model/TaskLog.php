<?php

namespace app\model;

use app\common\BaseModel;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\model\concern\SoftDelete;
use think\model\relation\BelongsTo;

/**
 * 任务执行日志模型
 * @property int $id
 * @property int $task_id
 * @property string $task_name
 * @property string $start_time
 * @property string $end_time
 * @property int $duration
 * @property int $status
 * @property string $output
 * @property string $error
 * @property int $pid
 * @property string $server_ip
 */
class TaskLog extends BaseModel
{
    use SoftDelete;

    protected $pk = 'task_log_id';
    protected string $deleteTime = 'deleted_at';
    protected bool $autoWriteTimestamp = true;

    // 执行状态
    const int STATUS_FAILED = 0;    // 失败
    const int STATUS_SUCCESS = 1;   // 成功
    const int STATUS_TIMEOUT = 2;   // 超时
    const int STATUS_CANCELED = 3;  // 取消

    /**
     * 记录任务开始执行
     * @param int $taskId
     * @param string $taskName
     * @param int $pid
     * @param string $serverIp
     * @return int|string
     */
    public static function recordStart(int $taskId, string $taskName, int $pid, string $serverIp): int|string
    {
        return self::create([
            'task_id' => $taskId,
            'task_name' => $taskName,
            'start_time' => date('Y-m-d H:i:s'),
            'pid' => $pid,
            'server_ip' => $serverIp,
            'status' => self::STATUS_SUCCESS // 初始设为成功，执行失败再更新
        ])->getKey();
    }

    /**
     * 记录任务执行结束
     * @param int $logId
     * @param int $status
     * @param string $output
     * @param string $error
     * @return bool
     */
    public static function recordEnd(int $logId, int $status, string $output = '', string $error = ''): bool
    {
        // 获取当前时间的毫秒时间戳
        $endTimeMs = microtime(true);
        
        // 将毫秒时间戳转换为年月日时分秒格式
        $endTime = date('Y-m-d H:i:s', (int)$endTimeMs);
        
        $log = (new TaskLog)->findOrEmpty($logId);

        if (!$log) {
            return false;
        }

        // 开始时间转换成秒时间戳
        $startTime = strtotime($log->start_time);
        
        // 结束时间毫秒时间戳减去开始时间秒时间戳，得到毫秒级时长
        $duration = (int)(($endTimeMs - $startTime) * 1000);

        return $log->save([
            'end_time' => $endTime,
            'duration' => $duration,
            'status' => $status,
            'output' => $output,
            'error' => $error
        ]);
    }

    /**
     * 关联任务
     * @return BelongsTo
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id', $this->getPk());
    }

    /**
     * 获取状态文本
     * @return string
     */
    public function getStatusTextAttr(): string
    {
        $statuses = [
            self::STATUS_FAILED => '失败',
            self::STATUS_SUCCESS => '成功',
            self::STATUS_TIMEOUT => '超时',
            self::STATUS_CANCELED => '取消'
        ];

        return $statuses[$this->status] ?? '未知';
    }
}
