<?php

namespace app\model;

use app\common\BaseModel;
use app\service\PermissionService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\model\relation\BelongsTo;
use think\model\relation\HasMany;

class Menu extends BaseModel
{
    protected $pk = 'menu_id';

    /**
     * 获取权限按钮
     * @param $adminId
     * @return array
     */
    public static function getUserButtons($adminId): array
    {
        // 检查是否为超级管理员
        if ((new Admin())->isSuper($adminId)) {
            // 超级管理员：获取所有OPTIONAL_BUTTON类型的所有依赖关系
            try {
                $dependencies = MenuPermissionDependency::where('permission_type', 'button')
                    ->select()
                    ->toArray();
            } catch (DataNotFoundException|DbException $e) {
                return [];
            }
        } else {
            // 普通用户：获取相关角色
            $roleIds = AdminRole::where('admin_id', $adminId)->column('role_id');
            if (empty($roleIds)) {
                return [];
            }

            // 获取用户关联的菜单ID
            $menuIds = RoleMenu::whereIn('role_id', $roleIds)->column('menu_id');
            if (empty($menuIds)) {
                return [];
            }
            $permissionIds = (new PermissionService)->getAdminPermissions($adminId);

            if (empty($permissionIds)) {
                return [];
            }

            // 获取按钮权限依赖
            try {
                $dependencies = MenuPermissionDependency::whereIn('menu_id', $menuIds)
                    ->where('permission_type', 'button')
                    ->whereIn('permission_id', $permissionIds)
                    ->select()
                    ->toArray();
            } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
                return [];
            }
        }

        if (empty($dependencies)) {
            return [];
        }

        // 高效获取菜单名和权限节点信息
        $menuIds = array_unique(array_column($dependencies, 'menu_id'));
        $menuMap = Menu::whereIn('menu_id', $menuIds)
            ->column('name', 'menu_id');

        $permissionIds = array_unique(array_column($dependencies, 'permission_id'));
        $permissionMap = Permission::whereIn('permission_id', $permissionIds)
            ->column('node', 'permission_id');

        // 组织按钮数据
        $buttons = [];
        foreach ($dependencies as $dep) {
            $menuName = $menuMap[$dep['menu_id']] ?? null;
            $permissionNode = $permissionMap[$dep['permission_id']] ?? null;

            if ($menuName && $permissionNode) {
                $buttonName = substr(strrchr($permissionNode, '/'), 1);  // 高效提取按钮名

                if (!isset($buttons[$menuName])) {
                    $buttons[$menuName] = [];
                }

                if (!in_array($buttonName, $buttons[$menuName])) {
                    $buttons[$menuName][] = $buttonName;
                }
            }
        }

        return $buttons;
    }

    /**
     * 获取用户可访问菜单树
     * @param $adminId
     * @return array
     */
    public static function getUserMenuTree($adminId, $request): array
    {
        $menus = self::getUserMenus($adminId, $request);
        return self::buildTree($menus);
    }

    /**
     * 获取用户可访问菜单
     * @param $adminId
     * @return array
     */
    public static function getUserMenus($adminId): array
    {
        if (empty($adminId)) {
            return [];
        }
        try {
            $permissionIds = (new PermissionService)->getAdminPermissions($adminId);
            return self::hasWhere('requiredPermission', function ($query) use ($permissionIds) {
                $query->whereIn('permission_id', $permissionIds);
            })->append(["meta"])
                ->hidden(["order_num", "is_link", "visible", "link_url", "is_full", "is_affix", "is_keep_alive", "required_permission_id", "created_at", "updated_at"])
                ->order("order_num asc")
                ->selectOrFail()
                ->each(function ($item) {

                    if (!empty($item['redirect'])) {
                        unset($item['component']);
                    }
                    return $item;
                })->toArray();
        } catch (DataNotFoundException|ModelNotFoundException $e) {
            (new Menu)->reportError($e->getMessage(), (array)$e, $e->getCode());
            return [];
        }
    }

    /**
     * 将扁平的菜单数据转换为树形结构
     * @param array $items 扁平的菜单数据数组
     * @param int $parentId 父级 ID，默认为 0
     * @param string|null $pk
     * @param string $parentFieldName
     * @param string $childrenName
     * @return array 树形结构的菜单数据
     */
    public static function buildTree(array $items, int $parentId = 0,string $pk=null ,string $parentFieldName="parent_id",string $childrenName="children"): array
    {
        if (empty($pk)){
            $pk= (new Menu)->getPk();
        }
        $tree = [];
        foreach ($items as $item) {
            if ($item[$parentFieldName] == $parentId) {
                $children = self::buildTree($items, $item[$pk],$pk,$parentFieldName,$childrenName);
                if ($children) {
                    $item[$childrenName] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }

    /**
     * 递归删除指定id的菜单/子级/和角色关联表
     * @param int $menuId
     * @return bool
     */
    public function deleteRecursive(int $menuId = 0): bool
    {
        if (empty($menuId)) {
            $menuId = $this->getKey();
        }
        if (empty($menuId)) {
            return $this->false("id缺失");
        }
        // 获取所有后代菜单ID（包括自身）
        $allMenuIds = $this->getAllDescendantIds($menuId);
        // 批量删除相关数据
        if (!empty($allMenuIds)) {
            $this->startTrans();
            try {
                // 一次性删除所有依赖
                (new MenuPermissionDependency)->whereIn($this->getPk(), $allMenuIds)->delete();
                // 一次性删除所有角色关联
                (new RoleMenu)->whereIn($this->getPk(), $allMenuIds)->delete();
                // 一次性删除所有菜单
                $this->whereIn($this->getPk(), $allMenuIds)->delete();
                $this->commit();
            } catch (\Exception $exception) {
                $this->rollback();
                return $this->false($exception->getMessage());
            }
        }
        return true;
    }

    /**
     * 获取所有后代菜单ID（包括自身）
     */
    private function getAllDescendantIds($menuId): array
    {
        $menuIds = [$menuId];
        // 递归获取所有子菜单ID
        $this->collectChildIds($menuId, $menuIds);
        return $menuIds;
    }

    /**
     * 递归收集子菜单ID
     */
    private function collectChildIds($parentId, array &$ids): void
    {
        $children = $this->where('parent_id', $parentId)->column($this->getPk());
        if (!empty($children)) {
            foreach ($children as $childId) {
                $ids[] = $childId;
                $this->collectChildIds($childId, $ids);
            }
        }
    }



    // 关联依赖权限

    public function requiredPermission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'required_permission_id');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(MenuPermissionDependency::class, 'menu_id');
    }

    /**
     * 获取菜单meta数据
     * @param [type] $value
     * @param [type] $data
     * @return array
     */
    public function getMetaAttr($value, $data): array
    {

        return [
            'icon' => $data['icon'],
            'title' => $data['title'],
            'isLink' => $data['link_url'] ?: false,
            'isHide' => !($data['visible'] == 1),
            'isFull' => $data['is_full'] == 1,
            'isAffix' => $data['is_affix'] == 1,
            'isKeepAlive' => $data['is_keep_alive'] == 1,
        ];

    }

    /**
     * 删除依赖关系
     * @param int $id
     * @return void
     * @throws \Exception
     */
    private function deleteDependencies(int $id = 0): void
    {
        if (empty($id)) {
            $id = $this->getKey();
        }
        if (empty($id)) {
            return;
        }
        if (!MenuPermissionDependency::where('menu_id', $id)->delete()) {
            throw new Exception("删除 主键为[{$this->getKey()}]的权限关联中间表数据 时出错");
        }
    }

    /**
     * 删除角色关联
     * @param int $id
     * @return void
     * @throws \Exception
     */
    private function deleteRoleRelations(int $id = 0): void
    {
        if (empty($id)) {
            $id = $this->getKey();
        }
        if (empty($id)) {
            return;
        }
        if (!RoleMenu::where('menu_id', $id)->delete()) {
            throw new Exception("删除 主键为[{$this->getKey()}]的角色关联中间表数据 时出错");
        }
    }
}