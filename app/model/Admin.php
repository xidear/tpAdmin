<?php

namespace app\model;

use app\common\BaseModel;
use think\facade\Cache;
use think\model\concern\SoftDelete;
use think\model\relation\BelongsToMany;

/**
 * @property string $password
 * @property string $username
 * @property string $type
 */
class Admin extends BaseModel
{
    protected $pk = 'admin_id';

    use SoftDelete;

    protected array $hidden = ['password'];
    protected string $deleteTime = 'deleted_at';

    public static function getInfoFromCache($adminId)
    {
        // 从缓存获取管理员信息
        $cacheKey = 'admin_info_' . $adminId;
        $admin = Cache::get($cacheKey);

        if (!$admin) {
            // 缓存未命中，从数据库获取
            $admin = (new Admin)->findOrEmpty($adminId);
            if ($admin->isEmpty()) {
                return [];
            }
            // 将管理员信息存入缓存，设置合理的过期时间，这里设为 3600 秒
            Cache::set($cacheKey, $admin, 86400);
        }
        return $admin;
    }

    /**
     * 关联角色
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, AdminRole::class, 'role_id', 'admin_id');
    }
    /**
     * 关联角色中间表
     */
    public function admin_role(): \think\model\relation\HasMany
    {
        return $this->hasMany(AdminRole::class, 'admin_id', 'admin_id');
    }
    /**
     *
     * 关联角色中间表
     */
    public function adminRole(): \think\model\relation\hasMany
    {
        return $this->hasMany(AdminRole::class, 'admin_id', 'admin_id');
    }
    /**
     * 是否超管
     * @param int $adminId
     * @return bool
     */
    public function isSuper(int $adminId = 0): bool
    {
        if (empty($adminId)) {
            $adminId = $this->getKey() ?: 0;
        }
        return $adminId == 1;
    }

    /**
     * 获取超管模型
     */
    public function getSuperAdmin(): Admin
    {

        return self::findOrEmpty(self::getSuperAdminId());
    }

    /**
     * 获取超管ID
     */
    public function getSuperAdminId(): int
    {

        return 1;
    }


}