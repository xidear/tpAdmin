<?php

namespace app\model;

use app\common\BaseModel;
use app\common\enum\YesOrNo;
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
    
    

    public function getMenuTreeWithPermissionAttr($value){
        return $this->menu_tree_with_permission();
    }

    /**
     * @return array
     */
    public function menu_tree_with_permission(): array
    {
        $roleId = $this->role_id; // 当前角色ID（模型实例必须包含role_id属性）
        if (empty($roleId)) {
            return []; // 若角色ID不存在，返回空数组
        }


//        已关联的菜单
        $menuIds=$this->role_menus()->column('menu_id');
//        已拥有的权限
        $permissionList=$this->permissions()->column("*","permission_id");

//        return $permissionList;
        $menuList = Menu::whereIn('menu_id',$menuIds)->order("order_num")->with(["dependencies"])->select()->each(function($item,$key) use($permissionList)   {
            $menuHasPermissionIds=$item->dependencies()->column('menu_id');
            foreach ($permissionList as $permissionId => $permission) {
                if (in_array($permissionId,$menuHasPermissionIds)) {
                    $item['role_has_permissions'][$permissionId]=$permission;
                }
            }
        });



        return $this->buildMenuTree($menuList->toArray());

        // 1. 获取当前角色拥有的所有菜单（从role_menu关联表）
        try {
            $menus = $this->menus()
                ->field('menu_id, name, icon, parent_id, order_num, visible, title, component')
                ->select()
                ->toArray();
        } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
            return [];
        }

        if (empty($menus)) {
            return []; // 无菜单时直接返回
        }

        // 2. 预加载所有菜单的权限依赖关系（减少数据库查询）
        $menuIds = array_column($menus, 'menu_id');
        try {
            $menuPermissionMap = $this->getMenuPermissionMap($menuIds);
        } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
            return [];
        } // 菜单ID => 关联的权限ID列表

        // 3. 预加载当前角色拥有的所有权限ID
        $rolePermissionIds = $this->rolePermissions()
            ->where('role_id', $roleId)
            ->column('permission_id');

        // 4. 预加载所有涉及的权限详情（避免重复查询）
        $allPermissionIds = array_unique(array_merge(...array_values($menuPermissionMap)));
        try {
            $permissionMap = $this->getPermissionMap($allPermissionIds);
        } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
            return [];
        } // 权限ID => 权限详情

        // 5. 构建菜单树并关联权限
        return $this->buildMenuTree($menus, 0, $menuPermissionMap, $rolePermissionIds, $permissionMap);
    }

    /**
     * 辅助函数：构建菜单树形结构（递归）
     * @param array $menus 平级菜单数组
     * @param int $parentId 父级ID（初始为0）
     * @param string $childrenKey
     * @return array 树形菜单数组
     */
    private function buildMenuTree(array $menus, int $parentId=0,string $parentKey="parent_id",string $childrenKey="children"): array
    {
        $tree = [];
        foreach ($menus as $menu) {
            if ($menu[$parentKey] != $parentId) {
                continue;
            }


            // 递归处理子菜单
            $children = $this->buildMenuTree($menus, $menu['menu_id'],$parentKey,$childrenKey);
            if (!empty($children)) {
                $menu[$childrenKey] = $children;
            }

            $tree[] = $menu;
        }

        // 按排序号排序
//        usort($tree, fn($a, $b) => $a['order_num'] - $b['order_num']);
        return $tree;
    }

    /**
     * 辅助函数：获取菜单与权限的映射关系（menu_id => [permission_id1, ...]）
     * @param array $menuIds
     * @return array
     * 辅助函数：获取菜单与权限的映射关系（menu_id => [permission_id1, ...]）
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function getMenuPermissionMap(array $menuIds): array
    {
        $dependencies = MenuPermissionDependency::whereIn('menu_id', $menuIds)
            ->field('menu_id, permission_id')
            ->select()
            // 使用 TP 的 each 方法处理每个元素，转换为整数
            ->each(function ($item) {
                $item->menu_id = (int)$item->menu_id;
                $item->permission_id = (int)$item->permission_id;
                return $item; // 注意：TP 的 each 需返回处理后的元素
            });

        // 转换为数组后，使用 TP 的集合方法或原生方法分组
        $map = [];
        foreach ($dependencies as $item) {
            $menuId = $item->menu_id;
            if (!isset($map[$menuId])) {
                $map[$menuId] = [];
            }
            $map[$menuId][] = $item->permission_id;
        }

        return $map;
    }

    /**
     * 辅助函数：获取权限详情映射（permission_id => 权限详情数组）
     * @param array $permissionIds
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function getPermissionMap(array $permissionIds): array
    {
        if (empty($permissionIds)) {
            return [];
        }

        // 1. 查询权限数据
        $permissions = Permission::whereIn('permission_id', $permissionIds)
            ->select()
            ->toArray();

        // 2. 手动构建以 permission_id 为键的数组
        $map = [];
        foreach ($permissions as $perm) {
            $permId = (int)$perm['permission_id']; // 确保键名为整数
            $map[$permId] = $perm;
        }

        return $map;
    }
//    public function menu_tree_with_permission(){
//
//        return [];
//        //这里获取 当前角色所拥有的所有菜单树,以及该菜单中的权限且已经分配给当前角色的权限,
//        //角色菜单表  role_menu INSERT INTO `tp_admin`.`role_menu` (`role_id`, `menu_id`) VALUES (1, 1);
//        //role_permission INSERT INTO `tp_admin`.`role_permission` (`role_id`, `permission_id`, `created_at`, `menu_id`) VALUES (1, 1, '2025-07-24 08:04:09', 1);
//        //menu INSERT INTO `tp_admin`.`menu` (`menu_id`, `name`, `icon`, `parent_id`, `order_num`, `visible`, `created_at`, `updated_at`, `is_link`, `is_full`, `is_affix`, `is_keep_alive`, `title`, `component`, `link_url`, `redirect`) VALUES (1, 'home', 'HomeFilled', 0, 0, 1, '2025-06-23 00:00:00', '2025-07-01 23:56:27', 2, 2, 1, 1, '首页', '/home/index', '', '');
//        //菜单 所拥有的 权限 INSERT INTO `tp_admin`.`menu_permission_dependency` (`dependency_id`, `menu_id`, `permission_id`, `type`, `description`, `created_at`, `permission_type`) VALUES (29, 60, 15, 'REQUIRED', NULL, '2025-07-20 22:27:43', 'data');
//        //权限表 INSERT INTO `tp_admin`.`permission` (`permission_id`, `node`, `name`, `description`, `created_at`, `updated_at`, `method`, `is_public`) VALUES (1, 'Login/doLogin', '用户登录', NULL, '2025-06-20 15:51:47', '2025-07-16 16:57:49', 'post', 1);
//
//    }


    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany('permission', RolePermission::class, 'permission_id', 'role_id');
    }

    public function admin_roles(): HasMany
    {
        return $this->hasMany(AdminRole::class, 'role_id', 'role_id');
    }

    public function adminRoles(): HasMany
    {
        return $this->hasMany(AdminRole::class, 'role_id', 'role_id');
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany('admin', AdminRole::class, 'admin_id', 'role_id');
    }

    public function menus(): BelongsToMany
    {
        return $this->belongsToMany('menu', RoleMenu::class, 'menu_id', 'role_id');
    }

    public function role_menus(): \think\model\relation\HasMany
    {
        return $this->hasMany(RoleMenu::class, 'role_id', 'role_id');
    }


    public function roleMenus(): \think\model\relation\HasMany
    {
        return $this->hasMany(RoleMenu::class, 'role_id', 'role_id');
    }

    public function role_permissions(): HasMany{
        return $this->hasMany(RolePermission::class, 'role_id', 'role_id');
    }

    public function rolePermissions(): HasMany{
        return $this->hasMany(RolePermission::class, 'role_id', 'role_id');
    }

}