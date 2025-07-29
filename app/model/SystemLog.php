<?php
namespace app\model;

use app\common\BaseModel;
use app\common\support\Tool;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Model;
use think\model\relation\BelongsTo;

class SystemLog extends BaseModel
{
    // 表名
    protected $name = 'system_logs';

    // 主键
    protected $pk = 'id';

    // 自动写入时间戳
    protected bool $autoWriteTimestamp = true;
    protected string $createTime = 'created_at';
    protected bool $updateTime = false;

    // 字段类型转换
    protected array $type = [
        'status' => 'integer',
        'execution_time' => 'float',
    ];

    /**
     * 管理员
     * @return BelongsTo
     */
    public function admin(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(Admin::class,'admin_id','admin_id');
    }

    /**
     * 获取用户已解析过的ua
     * @return array
     */
    public function getUaAttr(): array
    {
        return $this->ua();
    }
    /**
     * 获取用户已解析过的ua
     * @return array
     */
    public function ua(): array
    {
        return (new Tool())->parseUserAgent($this->getData("user_agent"));
    }
    /**
     * 批量记录日志（提高性能）
     * @param array $logs 日志数据数组
     * @return bool
     */
    public static function batchRecord(array $logs): bool
    {
        if (empty($logs)) {
            return true;
        }

        // 为每条日志添加创建时间
        $time = date('Y-m-d H:i:s');
        foreach ($logs as &$log) {
            if (!isset($log['created_at'])) {
                $log['created_at'] = $time;
            }
        }

        return (bool)(new SystemLog)->insertAll($logs);
    }

    /**
     * 按条件清理日志
     * @param int $days 保留天数
     * @return bool
     */
    public static function cleanLogs(int $days = 90): bool
    {
        $expireTime = date('Y-m-d H:i:s', strtotime("-$days days"));
        return (new SystemLog)->where('created_at', '<', $expireTime)->delete() !== false;
    }

    /**
     * 日志统计
     * @param string $startTime 开始时间
     * @param string $endTime 结束时间
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function statistics(string $startTime, string $endTime): array
    {
        return [
            'total' => self::whereBetween('created_at', [$startTime, $endTime])->count(),
            'success' => self::whereBetween('created_at', [$startTime, $endTime])->where('status', 1)->count(),
            'failed' => self::whereBetween('created_at', [$startTime, $endTime])->where('status', 0)->count(),
            'top_controllers' => self::whereBetween('created_at', [$startTime, $endTime])
                ->field('controller, count(*) as total')
                ->group('controller')
                ->order('total', 'desc')
                ->limit(10)
                ->select()
                ->toArray(),
        ];
    }
}
