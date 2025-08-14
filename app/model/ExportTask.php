<?php
namespace app\common\model;

use app\common\service\export\ExportService;
use think\Model;

/**
 * @property int $export_task_id
 * @property string $job_id
 * @property string $model_class
 * @property array $query_conditions
 * @property array $headers
 * @property string $filename
 * @property string $file_type
 * @property string $query_hash
 * @property string $permission_rule
 * @property int $created_by
 * @property int $priority
 * @property string $status
 * @property int $created_at
 * @property int $started_at
 * @property int $completed_at
 * @property string $error_msg
 * @property int $progress
 * @property string $file_path
 * @property int $data_count
 * @property string $data_version
 * @property int $expire_at
 * @property int $total_rows
 * @property int $exported_rows
 * @property int $is_reusable
 */
class ExportTask extends Model
{
    protected $pk = "export_task_id";
    protected string $table = "export_task";
    protected bool $autoWriteTimestamp = false;
    protected array $type = [
        'query_conditions' => "json",
        'headers' => "json",
        'created_at' => 'timestamp',
        'started_at' => 'timestamp',
        'completed_at' => 'timestamp',
        'expire_at' => 'timestamp'
    ];

    // 状态常量
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * 生成查询条件的哈希值
     */
    public static function generateQueryHash($model, $conditions, $headers, $fileType): string
    {
        $data = [
            'model_class' => $model,
            'conditions' => $conditions,
            'headers' => $headers,
            'type' => $fileType
        ];
        return md5(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 查找可复用的任务（带权限和数据校验）
     * @param string $queryHash 查询哈希
     * @param array $userRules 用户拥有的权限规则列表
     * @return static
     */
    public static function findReusableTask(string $queryHash, array $userRules)
    {
        $now = time();
        // 查找24小时内成功且可复用的任务
        $task = (new static)->where('query_hash', $queryHash)
            ->where('status', self::STATUS_SUCCESS)
            ->where('is_reusable', 1)
            ->where('created_at', '>', $now - 86400)
            ->where('expire_at', '>', $now)
            ->whereIn('permission_rule', $userRules)
            ->order('created_at', 'desc')
            ->findOrEmpty();

        if ($task->isEmpty()) {
            return $task;
        }

        // 校验数据是否发生变化
        return self::validateTaskDataVersion($task) ? $task : (new static)->findOrEmpty();
    }

    /**
     * 验证任务数据版本是否有效
     * @param ExportTask $task
     * @return bool
     */
    protected static function validateTaskDataVersion(ExportTask $task): bool
    {
        try {
            $exportService = new ExportService();

            // 从任务条件重建查询
            $query = (new $task->model_class())->buildQueryFromConditions($task->query_conditions);

            // 重新计算当前数据量
            $currentDataCount = $exportService->estimateDataCount($query);

            // 重新生成数据版本
            $currentLastUpdateTime = $exportService->getLastUpdateTime($task->model_class);
            $currentDataVersion = md5($currentDataCount . $currentLastUpdateTime);

            // 对比版本和数量
            return $task->data_version === $currentDataVersion && $task->data_count == $currentDataCount;
        } catch (\Exception $e) {
            // 验证过程出错，视为不可复用
            return false;
        }
    }

    /**
     * 查找相同条件的未完成任务（用于冲突检测）
     * @param string $queryHash 查询哈希
     * @param string $permissionRule 权限规则
     * @return static
     */
    public static function findPendingSameTask(string $queryHash, string $permissionRule)
    {
        return (new static)->where('query_hash', $queryHash)
            ->where('permission_rule', $permissionRule)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_PROCESSING])
            ->order('created_at', 'desc')
            ->findOrEmpty();
    }

    /**
     * 获取当前任务的排队位置
     * @return int 排队位置（0表示不在排队中，1表示第一个）
     */
    public function getQueuePosition(): int
    {
        // 如果任务已不是“待处理”状态，不在排队中
        if ($this->status != self::STATUS_PENDING) {
            return 0;
        }

        // 1. 统计优先级高于当前任务的待处理任务数量
        $higherPriorityCount = self::where('status', self::STATUS_PENDING)
            ->where('priority', '<', $this->priority) // 优先级数值越小越高
            ->count();

        // 2. 统计同优先级且创建时间早于当前任务的待处理任务数量
        $samePriorityEarlierCount = self::where('status', self::STATUS_PENDING)
            ->where('priority', '=', $this->priority)
            ->where('created_at', '<', $this->created_at) // 早于当前任务创建时间
            ->count();

        // 3. 总排队位置 = 高优先级任务数 + 同优先级早创建任务数 + 1（当前任务）
        return $higherPriorityCount + $samePriorityEarlierCount + 1;
    }

    /**
     * 克隆任务（用于重启功能）
     * @return $this
     */
    public function clone()
    {
        $clone = new self();
        $attributes = $this->getAttributes();

        // 移除自增主键和不需要复制的字段
        unset($attributes[$this->pk], $attributes['job_id'], $attributes['created_at'],
            $attributes['started_at'], $attributes['completed_at'], $attributes['status'],
            $attributes['progress'], $attributes['error_msg'], $attributes['file_path'],
            $attributes['expire_at'], $attributes['data_count'], $attributes['data_version']);

        $clone->setAttributes($attributes);
        return $clone;
    }
}
