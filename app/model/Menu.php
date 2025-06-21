<?php
namespace app\model;

use app\common\BaseModel;
use app\service\PermissionService;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;

class Menu extends BaseModel
{
    protected $pk = 'menu_id';

    // 关联必需权限
    public function requiredPermission(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(Permission::class, 'required_permission_id');
    }

    // 关联依赖权限
    public function dependencies(): \think\model\relation\HasMany
    {
        return $this->hasMany(MenuPermissionDependency::class, 'menu_id');
    }

    /**
     * 获取用户可访问菜单
     * @param $adminId
     * @return array
     */
    public static function getUserMenus($adminId): array
    {
        try {
            $permissionIds = (new PermissionService)->getAdminPermissions($adminId);

            return self::hasWhere('requiredPermission', function ($query) use ($permissionIds) {
                $query->whereIn('permission_id', $permissionIds);
            })->selectOrFail();
        } catch (DataNotFoundException|ModelNotFoundException $e) {
            (new Menu)->reportError($e->getMessage(),(array)$e,$e->getCode());
            return [];
        }
    }
}