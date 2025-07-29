<?php

namespace app\model;

use app\common\BaseModel;
use think\facade\Cache;
use think\model\concern\SoftDelete;

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
class TaskBak extends BaseModel
{
    use SoftDelete;

    protected $pk = 'id';
    protected string $deleteTime = 'deleted_at';

    // 任务类型
    const TYPE_COMMAND = 1; // 命令行
    const TYPE_URL = 2;     // URL请求
    const TYPE_PHP = 3;     // PHP方法

    // 运行平台
    const PLATFORM_ALL = 0;    // 全部
    const PLATFORM_LINUX = 1;  // Linux
    const PLATFORM_WINDOWS = 2;// Windows

    // 任务状态
    const STATUS_DISABLED = 0; // 禁用
    const STATUS_ENABLED = 1;  // 启用

    /**
     * 获取缓存键名
     * @param int $id
     * @return string
     */
    private static function getCacheKey(int $id): string
    {
        return "task_info_{$id}";
    }

    /**
     * 从缓存获取任务信息
     * @param int $id
     * @return array|static
     */
    public static function getInfoFromCache(int $id)
    {
        if (empty($id)) {
            return [];
        }

        $cacheKey = self::getCacheKey($id);
        $task = Cache::get($cacheKey);

        if (!$task) {
            $task = self::findOrEmpty($id);
            if ($task->isEmpty()) {
                return [];
            }
            Cache::set($cacheKey, $task, 86400);
        }

        return $task;
    }

    /**
     * 清除缓存
     * @param int $id
     * @return bool
     */
    public static function clearCache(int $id): bool
    {
        if (empty($id)) {
            return false;
        }

        return Cache::delete(self::getCacheKey($id));
    }

    /**
     * 批量清除缓存
     * @param array $ids
     */
    public static function clearCacheBatch(array $ids)
    {
        foreach ($ids as $id) {
            self::clearCache($id);
        }
    }

    /**
     * 获取需要执行的任务
     * @param string $platform 平台标识 linux/windows
     * @return array
     */
    public static function getPendingTasks(string $platform): array
    {
        $platformMap = [
            'linux' => self::PLATFORM_LINUX,
            'windows' => self::PLATFORM_WINDOWS
        ];

        $platformId = $platformMap[$platform] ?? self::PLATFORM_ALL;

        return self::where('status', self::STATUS_ENABLED)
            ->where(function($query) use ($platformId) {
                $query->where('platform', self::PLATFORM_ALL)
                    ->whereOr('platform', $platformId);
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

        if ($result) {
            self::clearCache($id);
        }

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
        $types = [
            self::TYPE_COMMAND => '命令行',
            self::TYPE_URL => 'URL请求',
            self::TYPE_PHP => 'PHP方法'
        ];

        return $types[$this->type] ?? '未知';
    }

    /**
     * 获取平台文本
     * @return string
     */
    public function getPlatformTextAttr(): string
    {
        $platforms = [
            self::PLATFORM_ALL => '全部',
            self::PLATFORM_LINUX => 'Linux',
            self::PLATFORM_WINDOWS => 'Windows'
        ];

        return $platforms[$this->platform] ?? '未知';
    }

    /**
     * 获取状态文本
     * @return string
     */
    public function getStatusTextAttr(): string
    {
        return $this->status == self::STATUS_ENABLED ? '启用' : '禁用';
    }
}
