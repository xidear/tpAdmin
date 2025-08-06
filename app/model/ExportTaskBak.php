<?php
namespace app\common\model;

use think\Model;

/**
 * @property int $created_at
 * @property int $priority
 * @property int $status
 * @property string $query_hash
 * @property string $file_type
 * @property string $filename
 * @property array $headers
 * @property array $query_conditions
 * @property string $model_class
 * @property string $job_id
 */
class ExportTask extends Model
{
    protected $pk="export_task_id";
    protected string $table="export_task";
    protected bool $autoWriteTimestamp = false;
    protected array $type = ['query_conditions'=>"json", 'headers'=>"json"];

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
     * 查找可复用的任务
     */
    public static function findReusableTask($queryHash)
    {
        // 查找24小时内成功且可复用的任务
        $expireTime = time() - 86400;
        return (new ExportTask)->where('query_hash', $queryHash)
            ->where('status', self::STATUS_SUCCESS)
            ->where('is_reusable', 1)
            ->where('created_at', '>', $expireTime)
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
}