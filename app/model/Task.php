<?php

namespace app\model;

use app\common\BaseModel;
use app\common\enum\Status;
use app\common\enum\task\TaskPlatform;
use app\common\enum\task\TaskType;
use app\common\enum\task\TaskExecuteMode;
use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Cache;
use think\Model;
use think\model\concern\SoftDelete;
use think\facade\Db;
use think\model\contract\Modelable;
use think\model\relation\HasMany;
use think\model\relation\HasOne;

/**
 * 定时任务模型
 * @property int $task_id
 * @property string $name
 * @property string $description
 * @property int $type
 * @property string $content
 * @property string $schedule
 * @property int $execute_mode 执行模式：1=循环，2=一次性
 * @property string $execute_at 一次性任务执行时间
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

    protected $pk = 'task_id';
    protected string $deleteTime = 'deleted_at';

    /**
     * 重写create方法，在创建循环任务时自动计算next_exec_time
     * @param array|object $data 创建数据
     * @param array $allowField
     * @param bool $replace
     * @param string $suffix
     * @return Modelable
     * @throws Exception
     */
    public static function create(array|object $data, array $allowField = [], bool $replace = false, string $suffix = ''): Modelable
    {
        // 如果是循环执行模式且next_exec_time为空，自动计算下次执行时间
        if (isset($data['execute_mode']) &&
            $data['execute_mode'] == TaskExecuteMode::LOOP->value &&
            (empty($data['next_exec_time'])) &&
            !empty($data['schedule'])) {

            // 使用TaskService中的方法计算下次执行时间
            $parser = new \Cron\CronExpression($data['schedule']);
            $data['next_exec_time'] = $parser->getNextRunDate()->format('Y-m-d H:i:s');
        }

        return parent::create($data);
    }

    /**
     * 获取需要执行的任务
     * @param string $platform 平台标识 linux/windows
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function getPendingTasks(string $platform): array
    {
        $platformMap = [
            'linux' => TaskPlatform::LINUX,
            'windows' => TaskPlatform::WINDOWS
        ];

        $platformId = $platformMap[$platform] ?? TaskPlatform::ALL;

        return (new Task)->where('status', Status::Normal->value)
            ->where(function($query) use ($platformId) {
                $query->where('platform', TaskPlatform::ALL->value)
                    ->whereOr('platform', $platformId->value);
            })
            ->where(function($query) {
                // 循环执行任务：检查next_exec_time
                $query->where('execute_mode', TaskExecuteMode::LOOP->value)
                    ->where('next_exec_time', '<=', date('Y-m-d H:i:s'));
            })
            ->whereOr(function($query) {
                // 一次性执行任务：检查execute_at且未执行过
                $query->where('execute_mode', TaskExecuteMode::ONCE->value)
                    ->where('execute_at', '<=', date('Y-m-d H:i:s'))
                    ->where('last_exec_time', 'null');
            })
            ->order('sort', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 更新任务执行时间
     * @param int $id
     * @param string $lastExecTime
     * @param string $nextExecTime
     * @return Modelable
     */
    public static function updateExecTime(int $id, string $lastExecTime, string $nextExecTime): \think\model\contract\Modelable
    {
        return (new Task)->where((new Task)->getPk(), $id)
            ->update([
                'last_exec_time' => $lastExecTime,
                'next_exec_time' => $nextExecTime,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }

    /**
     * 关联创建人
     * @return HasOne
     */
    public function creator(): HasOne
    {
        return $this->hasOne(Admin::class, 'admin_id', 'created_by')
            ->field('admin_id, username, avatar');
    }

    /**
     * 关联更新人
     * @return HasOne
     */
    public function updater(): HasOne
    {
        return $this->hasOne(Admin::class, 'admin_id', 'updated_by')
            ->field('admin_id, username, avatar');
    }

    /**
     * 关联执行日志
     * @return HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(TaskLog::class, 'task_id', $this->getPk())
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

    /**
     * 获取执行模式文本
     * @return string
     */
    public function getExecuteModeTextAttr(): string
    {
        return TaskExecuteMode::getValue($this->execute_mode);
    }
}
