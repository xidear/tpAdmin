<?php

namespace app\model;

use app\common\BaseModel;
use app\common\enum\task\YesOrNo;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\model\relation\BelongsToMany;
use think\model\relation\HasMany;

/**
 * @property int $role_id
 */
class Role extends BaseModel
{
    protected $pk = 'role_id';


    /**
     * 辅助函数：获取菜单与权限的映射关系（menu_id => [permission_id1, ...]）
     * @param array $menuIds
     * @return array
     * 辅助函数：获取菜单与权限的映射关系（menu_id => [permission_id1, ...]）
     */
    private function getMenuPermissionMap(array $menuIds): array
    {
        $dependencies = (new MenuPermissionDependency)->whereIn('menu_id', $menuIds)
            ->column('menu_id, permission_id');

        // 转换为数组后，使用 TP 的集合方法或原生方法分组
        $map = [];
        foreach ($dependencies as $item) {
            $menuId = $item['menu_id'];
            if (!isset($map[$menuId])) {
                $map[$menuId] = [];
            }
            $map[$menuId][] = $item['permission_id'];
        }

        return $map;
    }

    /**
     * 辅助函数：获取权限详情映射（permission_id => 权限详情数组）
     * @param array $permissionIds
     * @return array
     */
    private function getPermissionMap(array $permissionIds): array
    {
        if (empty($permissionIds)) {
            return [];
        }

        // 1. 查询权限数据
        return  (new Permission)->whereIn('permission_id', $permissionIds)
            ->column("*", "permission_id");
    }


    /**
     * 关联权限
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany('permission', RolePermission::class, 'permission_id', 'role_id');
    }

    /**
     * 关联管理员中间表
     * @return HasMany
     */
    public function admin_roles(): HasMany
    {
        return $this->hasMany(AdminRole::class, 'role_id', 'role_id');
    }

    /**
     * 关联管理员中间表
     * @return HasMany
     */
    public function adminRoles(): HasMany
    {
        return $this->hasMany(AdminRole::class, 'role_id', 'role_id');
    }

    /**
     * 关联管理员
     * @return BelongsToMany
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany('admin', AdminRole::class, 'admin_id', 'role_id');
    }

    /**
     * 关联菜单
     * @return BelongsToMany
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany('menu', RoleMenu::class, 'menu_id', 'role_id');
    }

    /**
     * 关联菜单中间表
     * @return HasMany
     */
    public function role_menus(): \think\model\relation\HasMany
    {
        return $this->hasMany(RoleMenu::class, 'role_id', 'role_id');
    }


    /**
     * 关联菜单中间表
     * @return HasMany
     */
    public function roleMenus(): \think\model\relation\HasMany
    {
        return $this->hasMany(RoleMenu::class, 'role_id', 'role_id');
    }

    /**
     * 关联权限中间表
     * @return HasMany
     */
    public function role_permissions(): HasMany{
        return $this->hasMany(RolePermission::class, 'role_id', 'role_id');
    }


    /**
     * 关联权限中间表
     * @return HasMany
     */
    public function rolePermissions(): HasMany{
        return $this->hasMany(RolePermission::class, 'role_id', 'role_id');
    }

}