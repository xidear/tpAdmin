<?php

namespace app\service;

use app\common\trait\BaseTrait;
use app\model\Admin;
use app\model\AdminRole;
use app\model\Menu;
use app\model\MenuPermissionDependency;
use app\model\Permission;
use app\model\RoleMenu;
use app\model\RolePermission;
use think\Exception;
use think\facade\Db;

class PermissionService
{
    use BaseTrait;

    /**获取用户所有权限ID
     * @param $adminId
     * @return array
     */
    public function getAdminPermissions($adminId): array
    {
        $where=[];
        if ((new Admin())->isSuper($adminId)){
            $permissionIds=(new Permission())->column("permission_id");
        }else{
            // 1. 获取用户所有角色
            $roleIds = (new AdminRole)->where('admin_id', $adminId)->column('role_id');
            $where[]=["role_id", "in", $roleIds];

            // 2. 获取角色直接分配的权限
            $permissionIds = (new \app\model\RolePermission)->where($where)
                ->column('permission_id');
        }


        return array_unique($permissionIds);
    }


    /**
     * 为角色分配菜单
     * @param int $roleId
     * @param array $menuIds
     * @param array $options
     * @return true
     * @throws \Exception
     */
    public function assignMenuToRole(int $roleId, array $menuIds, array $options = []): true
    {
        // 1. 移除旧菜单关联权限
        $this->clearRoleMenuPermissions($roleId, $menuIds);


        // 2. 添加新菜单相关权限
        foreach ($menuIds as $menuId) {
            $this->addMenuPermissionsToRole($roleId, $menuId, $options[$menuId] ?? []);
        }

        return true;
    }

    /**
     * 清除旧的角色菜单和角色权限关联
     * @param int $roleId
     * @param array $newMenuIds
     * @return bool
     * @throws \Exception
     */
    private function clearRoleMenuPermissions(int $roleId, array $newMenuIds): bool
    {
        // 1. 获取角色当前的菜单ID (正确方法)
        $currentMenuIds = (new RoleMenu())
            ->where('role_id', $roleId)
            ->column('menu_id');

        // 2. 找出需要移除的菜单ID
        $removeMenuIds = array_diff($currentMenuIds, $newMenuIds);

        if (empty($removeMenuIds)) {
            return true;
        }

        // 3. 获取这些菜单关联的权限ID
        $removePermissionIds = (new MenuPermissionDependency())
            ->whereIn('menu_id', $removeMenuIds)
            ->column('permission_id');

        Db::startTrans();
        try {
            if (!empty($removePermissionIds)) {
                // 4. 从角色权限中移除这些权限
                if (!(new  RolePermission())
                    ->where('role_id', $roleId)
                    ->whereIn('permission_id', $removePermissionIds)
                    ->delete()) {
                    throw new Exception("删除角色权限关联失败");
                }
            }

            if (!empty((new RoleMenu())
                ->where('role_id', $roleId)
                ->whereIn('menu_id', $removeMenuIds)
                ->delete())) {
                throw new \Exception("删除角色菜单关联失败");
            }
            return true;
        } catch (\Exception $e) {

            Db::rollback();
            $this->reportError($e->getMessage(), (array)$e, $e->getCode());


            throw new Exception($e->getMessage());
        }


    }

    /**
     * @param int $roleId
     * @param int $menuId
     * @param array $options
     * @return bool
     * @throws \Exception
     */
    private function addMenuPermissionsToRole(int $roleId, int $menuId, array $options): bool
    {
        $menu = Menu::with('dependencies')->findOrFail($menuId);

        // 添加基础权限
        $this->addPermissionToRole($roleId, $menu->required_permission_id);

        // 添加依赖权限
        foreach ($menu->dependencies as $dependency) {
            // 只添加必需权限和选中的可选权限
            if ($dependency->type === 'REQUIRED' ||
                ($dependency->type === 'OPTIONAL_BUTTON' && in_array($dependency->permission_id, $options))) {
                $this->addPermissionToRole($roleId, $dependency->permission_id);
            }
        }

        return true;
    }

    /**
     * @param $roleId
     * @param $permissionId
     * @return void
     * @throws \Exception
     */
    private function addPermissionToRole($roleId, $permissionId): void
    {
        $result = (new RolePermission)->firstOrCreate([
            'role_id' => $roleId,
            'permission_id' => $permissionId
        ]);
        if (!$result || $result->isEmpty()) {
            throw new Exception("创建失败");
        }
    }

    /**
     * 是否拥有某个权限
     * @param int|null $adminId
     * @param string $nodeName
     * @param string $methodName
     * @return bool
     */
    public function check(?int $adminId, string $nodeName, string $methodName = "get"): bool
    {
        if (empty($adminId)||empty($nodeName)||empty($methodName)) {
            return false;
        }
        return (new RolePermission())->alias('rp')
                ->join("Permission p", "p.permission_id = rp.permission_id")
                ->join("AdminRole ar", "ar.role_id = rp.role_id")
                ->where("ar.admin_id", $adminId)
                ->where("p.node", $nodeName)
                ->where("p.method", $methodName)
                ->count() > 0;
    }
}