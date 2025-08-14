<?php

namespace app\common\service;

use app\common\BaseService;
use app\common\trait\BaseTrait;
use app\middleware\AuthCheck;
use app\middleware\AutoPermissionCheck;
use app\model\Admin;
use app\model\AdminRole;
use app\model\Menu;
use app\model\MenuPermissionDependency;
use app\model\Permission;
use app\model\RoleMenu;
use app\model\RolePermission;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\facade\Db;
use think\facade\Route;

class PermissionService extends BaseService
{

    /**
     * 同步指定前缀的路由到权限表（适配TP框架）
     * @param string $prefix
     * @param bool $deleteInvalid
     * @return array|bool
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function sync(string $prefix, bool $deleteInvalid = false): array|bool
    {
        $formattedRoutes = $this->getFormattedRoutes($prefix);
        if (empty($formattedRoutes)) {
            return $this->true("未匹配到指定前缀的路由");
        }

        // 新增验证：需要权限验证的节点必须要求登录
        $invalidRoutes = [];
        foreach ($formattedRoutes as $route) {
            // need_permission=1 表示需要权限验证
            // need_login=2 表示不需要登录
            if ($route['need_permission'] == 1 && $route['need_login'] == 2) {
                $invalidRoutes[] = "节点: {$route['node']} 方法: {$route['method']}";
            }
        }
        if (!empty($invalidRoutes)) {
            $errorMsg = "以下节点需要权限验证但未设置登录要求：\n" . implode("\n", $invalidRoutes);
            return $this->false($errorMsg);
        }



        $routeMap = [];
        foreach ($formattedRoutes as $route) {
            $key = $route['node'] . '|' . $route['method'];
            if (!isset($routeMap[$key])) {
                $routeMap[$key] = $route;
            }
        }
        $formattedRoutes = array_values($routeMap);

        $existingPermissions = Permission::field('permission_id, node, method')->select()->toArray();
        $existingMap = [];
        $existingKeys = [];
        foreach ($existingPermissions as $item) {
            $key = $item['node'] . '|' . $item['method'];
            $existingMap[$key] = $item['permission_id'];
            $existingKeys[] = $key;
        }

        $saveData = [];
        foreach ($formattedRoutes as $route) {
            $key = $route['node'] . '|' . $route['method'];
            if (isset($existingMap[$key])) {
                $route['permission_id'] = $existingMap[$key];
            }
            $saveData[] = $route;
        }

        $routeKeys = array_keys($routeMap);
        $deleteKeys = array_diff($existingKeys, $routeKeys);
        if (!empty($deleteKeys)) {
            $deleteWhere = [];
            foreach ($deleteKeys as $key) {
                list($node, $method) = explode('|', $key);
                $deleteWhere[] = [
                    ['node', '=', $node],
                    ['method', '=', $method],
                ];
            }

        }
        $model = new Permission();
        $model->startTrans();
        try {
            $msg=["处理完成"];
            if (!empty($saveData)){
                $result = $model->saveAll($saveData);
                $msg[] = "同步成功，处理 " . $result->count() . " 条数据";
            }
            if (!empty($deleteWhere)) {
                $deleteCount = Permission::whereOr($deleteWhere)->delete();
                if ($deleteInvalid) {
                    $msg[] = "，删除无效记录 " . $deleteCount . " 条";
                }
            }
            $model->commit();
        } catch (\Exception $e) {
            $model->rollback();
            $this->reportError($e->getMessage(), (array)$e, $e->getCode());
            return $this->false($e->getMessage());
        }
        return $this->true(implode(";",$msg));

    }

    /**
     * 格式化路由（TP专用逻辑）
     * @param string $prefix
     * @return array
     */
    private function getFormattedRoutes(string $prefix): array
    {
        $ruleList = Route::getRuleList();
        if (empty($ruleList)) {
            return [];
        }

        $formatted = [];
        foreach ($ruleList as $rule) {
            if ($rule['rule'] === '<MISS>') {
                continue;
            }

            if (!str_starts_with($rule['rule'], $prefix) || empty($rule['route'])) {
                continue;
            }

            $methods = $this->resolveHttpMethods($rule['method']);
            $routePrefix = $rule['option']['prefix'] ?? '';
            $fullHandler = $this->getFullHandler($routePrefix, $rule['route']);
            $node = $this->generateNode($fullHandler);
            if (empty($node)) {
                continue;
            }

            $middleware = $rule['option']['middleware'] ?? [];
            $middlewareClasses = $this->resolveMiddleware($middleware);

            foreach ($methods as $method) {
                $formatted[] = [
                    'node' => $node,
                    'name' => $rule['name'] ?: ucfirst(str_replace('/', ' ', $node)) . "({$method})",
                    'description' => $rule['option']['description'] ?? "路由地址：{$rule['rule']}",
                    'method' => $method,
                    'need_login' => in_array(AuthCheck::class, $middlewareClasses) ? 1 : 2,
                    'rule' => $rule['rule'],
                    'need_permission' => in_array(AutoPermissionCheck::class, $middlewareClasses) ? 1 : 2
                ];
            }
        }

        return $formatted;
    }

    /**
     * 解析HTTP方法（TP兼容）
     * @param string $method
     * @return array
     */
    private function resolveHttpMethods(string $method): array
    {
        $method = strtolower(trim($method));
        if ($method === 'any' || $method === '*') {
            return ['*'];
        }
        $methods = explode(',', $method);
        return array_map('strtoupper', $methods);
    }

    /**
     * @param string $prefix
     * @param string $handler
     * @return string
     */
    private function getFullHandler(string $prefix, string $handler): string
    {
        if (empty($prefix)) {
            return $handler;
        }
        $prefix = rtrim($prefix, '/');
        return $prefix . '/' . ltrim($handler, '/');
    }

    /**
     * @param string $fullHandler
     * @return string
     */
    private function generateNode(string $fullHandler): string
    {
        $parts = array_filter(explode('/', $fullHandler));
        $parts = array_values($parts);

        if (count($parts) >= 2) {
            $controller = $parts[count($parts) - 2];
            $action = $parts[count($parts) - 1];
            return "{$controller}/{$action}";
        }
        return '';
    }

    /**
     * 解析中间件（TP格式）
     * @param array $middleware
     * @return array
     */
    private function resolveMiddleware(array $middleware): array
    {
        $classes = [];
        foreach ($middleware as $m) {
            if (is_array($m)) {
                $class = $m[0] ?? '';
                if (is_string($class) && class_exists($class)) {
                    $classes[] = $class;
                }
            } elseif (is_string($m) && class_exists($m)) {
                $classes[] = $m;
            }
        }
        return $classes;
    }

    /**
     * 获取用户权限（TP查询）
     * @param $adminId
     * @return array
     */
    public function getAdminPermissions($adminId): array
    {
        if ((new Admin())->isSuper($adminId)) {
            return Permission::column("permission_id");
        }

        $roleIds = AdminRole::where('admin_id', $adminId)->column('role_id');
        if (empty($roleIds)) {
            return [];
        }

        return RolePermission::where('role_id', 'in', $roleIds)
            ->column('permission_id');
    }

    /**
     * 分配菜单给角色（TP事务）
     * @param int $roleId
     * @param array $menuIds
     * @param array $options
     * @return true
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function assignMenuToRole(int $roleId, array $menuIds, array $options = []): true
    {
        $this->clearRoleMenuPermissions($roleId, $menuIds);
        foreach ($menuIds as $menuId) {
            $this->addMenuPermissionsToRole($roleId, $menuId, $options[$menuId] ?? []);
        }
        return true;
    }

    /**
     * 清除角色菜单权限（TP语法）
     * @param int $roleId
     * @param array $newMenuIds
     * @return bool
     * @throws \Exception
     */
    private function clearRoleMenuPermissions(int $roleId, array $newMenuIds): bool
    {
        $currentMenuIds = RoleMenu::where('role_id', $roleId)->column('menu_id');
        $removeMenuIds = array_diff($currentMenuIds, $newMenuIds);
        if (empty($removeMenuIds)) {
            return true;
        }

        $removePermissionIds = MenuPermissionDependency::whereIn('menu_id', $removeMenuIds)
            ->column('permission_id');

        Db::startTrans();
        try {
            if (!empty($removePermissionIds)) {
                RolePermission::where('role_id', $roleId)
                    ->whereIn('permission_id', $removePermissionIds)
                    ->delete();
            }

            RoleMenu::where('role_id', $roleId)->whereIn('menu_id', $removeMenuIds)->delete();
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            $this->reportError($e->getMessage(), (array)$e, $e->getCode());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 添加菜单权限到角色（TP模型）
     * @param int $roleId
     * @param int $menuId
     * @param array $options
     * @return bool
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    private function addMenuPermissionsToRole(int $roleId, int $menuId, array $options): bool
    {
        $menu = Menu::with('dependencies')->findOrFail($menuId);
        $this->addPermissionToRole($roleId, $menu->required_permission_id);

        foreach ($menu->dependencies as $dependency) {
            if ($dependency->type === 'REQUIRED' ||
                ($dependency->type === 'OPTIONAL_BUTTON' && in_array($dependency->permission_id, $options))) {
                $this->addPermissionToRole($roleId, $dependency->permission_id);
            }
        }
        return true;
    }

    /**
     * 添加角色权限（TP原生判断）
     * @param $roleId
     * @param $permissionId
     * @throws \Exception
     */
    private function addPermissionToRole($roleId, $permissionId): void
    {
        if (empty($permissionId)) {
            return;
        }

        // TP判断记录是否存在的正确方式
        $exists = RolePermission::where([
            'role_id' => $roleId,
            'permission_id' => $permissionId
        ])->findOrEmpty();

        if (!$exists->isEmpty()) {
            $result = RolePermission::create([
                'role_id' => $roleId,
                'permission_id' => $permissionId
            ]);

            if ($result->isEmpty()) {
                $this->reportError("角色权限关联创建失败", ['role_id' => $roleId, 'permission_id' => $permissionId], 500);
                throw new Exception("角色权限关联创建失败");
            }
        }
    }

    /**
     * 权限检查（TP查询语法）
     */
    public function check(?int $adminId, string $nodeName, string $methodName = "GET"): bool
    {
        if (empty($adminId) || empty($nodeName) || empty($methodName)) {
            return $this->false("缺失用户|节点|请求方法");
        }

        // 超级管理员直接通过
        if ((new Admin())->isSuper($adminId)) {
            return $this->true("超级管理员");
        }

        $upperMethod = strtoupper($methodName);
        // TP判断是否存在的正确方式：使用find()或count()
        $count = RolePermission::alias('rp')
            ->join("permission p", "p.permission_id = rp.permission_id")
            ->join("admin_role ar", "ar.role_id = rp.role_id")
            ->where("ar.admin_id", $adminId)
            ->where("p.node", $nodeName)
            ->where(function ($query) use ($upperMethod) {
                $query->where("p.method", $upperMethod)
                    ->whereOr("p.method", "*");
            })
            ->count();

        if ($count<=0){
            return $this->false("用户[{$adminId}]没有指定权限[{$nodeName}@{$methodName}]");
        }
        return true;
    }
}
    