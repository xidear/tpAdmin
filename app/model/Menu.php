<?php

namespace app\model;

use app\common\BaseModel;
use app\common\trait\TreeTrait;
use app\common\enum\menu\MenuPermissionDependencies;
use app\common\service\PermissionService;
use app\Request;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\model\relation\BelongsTo;
use think\model\relation\BelongsToMany;
use think\model\relation\HasMany;

class Menu extends BaseModel
{
    protected $pk = 'menu_id';

    use TreeTrait;

    /**
     * 初始化树形结构配置
     */
    protected function initialize()
    {
        parent::initialize();
        
        // 设置树形结构配置
        $this->setTreeConfig([
            'parentKey' => 'parent_id',
            'primaryKey' => 'menu_id',
            'pathKey' => 'path',
            'levelKey' => 'level',
            'nameKey' => 'name',
            'childrenKey' => 'children',
            'pathSeparator' => ',',
            'sortKey' => 'order_num',
            'statusKey' => 'visible',
            'deletedAtKey' => 'deleted_at',
        ]);
    }

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
            $permissionIds = (new PermissionService(new App()))->getAdminPermissions($adminId);

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
        $menuMap = (new Menu)->whereIn('menu_id', $menuIds)
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
     * @param int|null $adminId
     * @param Request|null $request
     * @return array
     */
    public static function getUserMenuTree(?int $adminId, ?Request $request): array
    {
        return self::buildMenuTree(self::getUserMenus($adminId, $request));
    }

    /**
     * 将扁平的菜单数据转换为树形结构并生成路径
     * @param array $items 扁平的菜单数据数组
     * @param int $parentId 父级 ID，默认为 0
     * @param string|null $pk
     * @param string $parentFieldName
     * @param string $childrenName
     * @param string $parentPath 当前父级路径，用于生成子菜单路径
     * @return array 树形结构的菜单数据
     */
    public static function buildMenuTree(array $items, int $parentId = 0, string $pk = null, string $parentFieldName = "parent_id", string $childrenName = "children", string $parentPath = ''): array
    {
        if (empty($pk)) {
            $pk = (new Menu)->getPk();
        }
        $tree = [];

        foreach ($items as $item) {
            if ($item[$parentFieldName] == $parentId) {
                // 计算当前菜单的基础路径
                $basePath = $parentPath
                    ? "{$parentPath}/{$item['name']}"
                    : "/{$item['name']}";

                // 递归查找子菜单
                $children = self::buildMenuTree(
                    $items,
                    $item[$pk],
                    $pk,
                    $parentFieldName,
                    $childrenName,
                    $basePath // 传递当前路径作为子菜单的父路径
                );

                // 判断是否有子菜单
                $hasChildren = !empty($children);

                // 根据是否有子菜单决定路径是否添加/index
                $item['path'] = $hasChildren ? $basePath : "{$basePath}/index";

                // 如果有子菜单，添加到当前节点
                if ($hasChildren) {
                    $item[$childrenName] = $children;
                }

                $tree[] = $item;
            }
        }

        return $tree;
    }

    /**
     * 获取用户可访问菜单
     * @param int|null $adminId
     * @param Request|null $request
     * @return array
     */
    public static function getUserMenus(?int $adminId, ?Request $request = null): array
    {
        if (empty($adminId)) {
            return [];
        }

        if ((new Admin)->isSuper($adminId)) {
            $menuIds = Menu::where("1=1")->column('menu_id');
        } else {
            $roleIds = AdminRole::where("admin_id", $adminId)->column('role_id');
            if (empty($roleIds)) {
                return [];
            }
            $menuIds = RoleMenu::whereIn('role_id', $roleIds)->column('menu_id');

            if (empty($menuIds)) {
                return [];
            }
        }
        try {
            return (new Menu)
                ->hidden(["order_num", "is_link", "visible", "link_url", "is_full", "is_affix", "is_keep_alive", "created_at", "updated_at"])
                ->order("order_num asc")
                ->append(["meta"])
                ->selectOrFail($menuIds)
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
     * 获取用户可访问菜单树
     * @param Request|null $request
     * @return array
     */
    public static function getMenuTree(?Request $request): array
    {
        return self::buildMenuTree(self::getUserMenus((new Admin())->getSuperAdminId(), $request));
    }

    /**
     * 所有菜单树,不是菜单结构
     * @return array
     */
    public static function getAllMenuTree(): array
    {
        try {
            return self::buildMenuTree(self::where("1=1")->with(["permissions"])->order("order_num asc")
                ?->select()
                ?->toArray() ?? []);
        } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
            return [];
        }
    }

    /**
     * 父级菜单
     * @return BelongsTo
     */
    public function parent_menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id', 'menu_id');
    }

    /**
     * 父级菜单
     * @return BelongsTo
     */
    public function parentMenu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id', 'menu_id');
    }

    /**
     * 子级菜单
     * @return HasMany
     */
    public function childrenMenu(): HasMany
    {
        return $this->hasMany(Menu::class, 'menu_id', 'menu_id');
    }

    /**
     * 子级菜单
     * @return HasMany
     */
    public function children_menu(): HasMany
    {
        return $this->hasMany(Menu::class, 'menu_id', 'menu_id');
    }

    /**
     * 必备权限
     * @return HasMany
     */
    public function requiredPermission(): HasMany
    {
        return $this->requiredPermissionDependencies();
    }

    /**
     * 必备权限中间表
     * @return HasMany
     */
    public function requiredPermissionDependencies(): HasMany
    {
        return $this->dependencies()->where("type", MenuPermissionDependencies::Required->value);
    }

    /**
     * 已有权限中间表
     * @return HasMany
     */
    public function dependencies(): HasMany
    {
        return $this->hasMany(MenuPermissionDependency::class, 'menu_id');
    }

    /**
     * 必备权限
     * @return HasMany
     */
    public function required_permission(): HasMany
    {
        return $this->requiredPermissionDependencies();
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
     * 必备权限中间表
     * @return HasMany
     */
    public function required_permission_dependencies(): HasMany
    {
        return $this->dependencies()->where("type", MenuPermissionDependencies::Required->value);
    }

    /**
     * 已有权限
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {

        return $this->belongsToMany(Permission::class, MenuPermissionDependency::class);
    }

    /**
     * 已有权限中间表
     * @return HasMany
     */
    public function notRequiredDependencies(): HasMany
    {
        return $this->hasMany(MenuPermissionDependency::class, 'menu_id')->where("type", MenuPermissionDependencies::Optional->value);
    }

    /**
     * 已有权限中间表
     * @return HasMany
     */
    public function not_required_dependencies(): HasMany
    {
        return $this->hasMany(MenuPermissionDependency::class, 'menu_id')->where("type", MenuPermissionDependencies::Optional->value);
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

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, RoleMenu::class, 'menu_id', 'role_id');
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