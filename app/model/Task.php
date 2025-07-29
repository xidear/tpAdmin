<?php

namespace app\model;

use app\common\BaseModel;
use app\common\enum\Status;
use app\common\enum\TaskPlatform;
use app\common\enum\TaskType;
use think\facade\Cache;
use think\model\concern\SoftDelete;
use think\facade\Db;

/**
 * 定时任务模型
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $type
 * @property string $content
 * @property string $schedule
 * @property int $status
 * @property int $platform
 * @property string $exec_user
 * @property int $timeout
 * @property int $retry
 * @property int $interval
 * @property string $last_exec_time
 * @property string $next_exec_time
 * @property int $sort
 * @property int $created_by
 * @property int $updated_by
 */
class Task extends BaseModel
{
    use SoftDelete;

    protected $pk = 'id';
    protected string $deleteTime = 'deleted_at';




    /**
     * 获取需要执行的任务
     * @param string $platform 平台标识 linux/windows
     * @return array
     */
    public static function getPendingTasks(string $platform): array
    {
        $platformMap = [
            'linux' => TaskPlatform::LINUX,
            'windows' => TaskPlatform::WINDOWS
        ];

        $platformId = $platformMap[$platform] ?? TaskPlatform::ALL;

        return self::where('status', Status::Normal->value)
            ->where(function($query) use ($platformId) {
                $query->where('platform', TaskPlatform::ALL->value)
                    ->whereOr('platform', $platformId->value);
            })
            ->where('next_exec_time', '<=', date('Y-m-d H:i:s'))
            ->order('sort', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 更新任务执行时间
     * @param int $id
     * @param string $lastExecTime
     * @param string $nextExecTime
     * @return bool
     */
    public static function updateExecTime(int $id, string $lastExecTime, string $nextExecTime): bool
    {
        $result = self::where('id', $id)
            ->update([
                'last_exec_time' => $lastExecTime,
                'next_exec_time' => $nextExecTime,
                'updated_at' => date('Y-m-d H:i:s')
            ]);


        return $result !== false;
    }

    /**
     * 关联创建人
     * @return \think\model\relation\HasOne
     */
    public function creator()
    {
        return $this->hasOne(Admin::class, 'admin_id', 'created_by')
            ->field('admin_id, username, avatar');
    }

    /**
     * 关联更新人
     * @return \think\model\relation\HasOne
     */
    public function updater()
    {
        return $this->hasOne(Admin::class, 'admin_id', 'updated_by')
            ->field('admin_id, username, avatar');
    }

    /**
     * 关联执行日志
     * @return \think\model\relation\HasMany
     */
    public function logs()
    {
        return $this->hasMany(TaskLog::class, 'task_id', 'id')
            ->order('start_time', 'desc');
    }

    /**
     * 获取任务类型文本
     * @return string
     */
    public function getTypeTextAttr(): string
    {

        return TaskType::getValue($this->type);
    }

    /**
     * 获取平台文本
     * @return string
     */
    public function getPlatformTextAttr(): string
    {
        return TaskPlatform::getValue($this->platform);

    }

    /**
     * 获取状态文本
     * @return string
     */
    public function getStatusTextAttr(): string
    {
        return Status::getValue($this->status);
    }
}
