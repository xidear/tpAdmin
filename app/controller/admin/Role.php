<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\common\enum\DependenciesType;
use app\model\MenuPermissionDependency;
use app\model\Role as RoleModel;
use app\request\admin\role\assignMenu;
use app\request\admin\role\Create;
use app\request\admin\role\Delete;
use app\request\admin\role\Edit;
use app\request\admin\role\Read;
use app\service\PermissionService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use think\Response;
use Throwable;

class Role extends BaseController
{
    // 角色列表
    public function index(): Response
    {
        $roles = (new RoleModel)->fetchData();
        return $this->success($roles);
    }


    /**
     * @param $role_id
     * @param Read $read
     * @return Response
     */
    public function read($role_id, Read $read): Response
    {

        $info = (new RoleModel())
            ->fetchOne($role_id, [
                'with' => ['admins' => function ($query) {
                    $query->field('admin_id,real_name,username')->hidden(['pivot']);
                }, 'admin_roles', 'role_permissions', 'role_menus']]);
        return $this->success($info);
    }


    /**
     * @param Create $create
     * @return Response
     */
    public function create(Create $create): Response
    {

        $params = $create->param();

        // 调用公共处理方法（新增时无角色ID，传null）
        $processedParams = $this->processRoleParams($params, null);

        // 创建角色（模型需支持自动关联role_permissions和role_menus）
        $roleModel = new RoleModel();

        $result = $roleModel->intelligentCreate($processedParams);

        if (!$result->isEmpty()) {
            return $this->success($result);
        }

        return $this->error($roleModel->getMessage() ?: "角色创建失败");

    }



    /**
     * 编辑角色
     * @param $role_id
     * @param Edit $edit
     * @return Response
     */
    public function update($role_id, Edit $edit): Response
    {
        $params =$edit->param();
        $roleModel = new RoleModel();
        $info = $roleModel->fetchOne($role_id);

        if ($info->isEmpty()) {
            return $this->error("未找到指定数据");
        }

        // 调用公共处理方法（编辑时传入角色ID）
        $processedParams = $this->processRoleParams($params, $role_id);

        // 模型智能更新（自动处理关联关系）
        if ($info->intelligentUpdate($processedParams)) {
            return $this->success("角色更新成功");
        }

        return $this->error($info->getMessage() ?: "角色更新失败");
    }

    /**
     * 公共参数处理方法（供新增和编辑复用）
     * @param array $params 原始参数
     * @param int|null $roleId 角色ID（新增时为null，编辑时为具体ID）
     * @return array 处理后的参数
     */
    private function processRoleParams(array $params, ?int $roleId): array
    {
        // 1. 清理关联数据中的role_id（新增和编辑都需要）
        $this->cleanRoleIdInRelations($params);

        // 2. 补充缺失的必备权限（新增和编辑都需要）
        $params['role_permissions'] = $this->supplementRequiredPermissions(
            $params['role_permissions'] ?? []
        );

        // 3. 移除顶级role_id参数（新增时可能不存在，编辑时需移除）
        unset($params['role_id']);

        return $params;
    }

    /**
     * 清理关联数据中的role_id字段
     * @param array $params
     */
    private function cleanRoleIdInRelations(array &$params): void
    {
        // 清理角色权限中的role_id
        if (!empty($params['role_permissions']) && is_array($params['role_permissions'])) {
            foreach ($params['role_permissions'] as &$item) {
                if (is_array($item) && isset($item['role_id'])) {
                    unset($item['role_id']);
                }
            }
        }

        // 清理角色菜单中的role_id
        if (!empty($params['role_menus']) && is_array($params['role_menus'])) {
            foreach ($params['role_menus'] as &$item) {
                if (is_array($item) && isset($item['role_id'])) {
                    unset($item['role_id']);
                }
            }
        }
    }

    /**
     * 补充缺失的必备权限
     * @param array $submittedPermissions 前端提交的权限列表
     * @return array 补充后的权限列表
     */
    private function supplementRequiredPermissions(array $submittedPermissions): array
    {
        // 查询所有必备权限（type=REQUIRED）
        $requiredPermissions = ( new MenuPermissionDependency())
            ->where('type', 'REQUIRED')
            ->column('menu_id, permission_id');

        if (empty($requiredPermissions)) {
            return $submittedPermissions;
        }

        // 提取已提交的权限组合（去重）
        $submittedPairs = [];
        foreach ($submittedPermissions as $item) {
            if (isset($item['menu_id'], $item['permission_id'])) {
                $key = "{$item['menu_id']}_{$item['permission_id']}";
                $submittedPairs[$key] = true;
            }
        }

        // 补充缺失的必备权限
        foreach ($requiredPermissions as $required) {
            $key = "{$required['menu_id']}_{$required['permission_id']}";
            if (!isset($submittedPairs[$key])) {
                $submittedPermissions[] = [
                    'menu_id' => $required['menu_id'],
                    'permission_id' => $required['permission_id'],
                ];
            }
        }

        return $submittedPermissions;
    }
//    public function update($role_id, Edit $edit): Response
//    {
////        未完成
//        $params = $this->request->param();
//        $info = (new RoleModel())->fetchOne($role_id);
//        if ($info->isEmpty()) {
//            return $this->error("未找到指定数据");
//        }
//
//        //需要验证的数据(请注意 在新增的时候要复用这套逻辑)
//        //      1.前端传过来的 $params['role_permissions'] 里面的 menu_id 和 permission_id 是否一一对应且存在于 MenuPermissionDependency,里面如果有 role_id ,role_id是否和当前 $role_id相同
////              2.前端传来的 $params['role_menus'] 里面的 menu_id 是否真实存在.里面如果有 role_id ,role_id是否和当前 $role_id相同
////                3.检查 $params['role_permissions'] 里面是否缺失必备权限,如果缺失则补充进去
////        4.以上验证都要验证是否有重复数据 比如 role_menu里面 menu_id有重复的 role_permission里面 menu_id和permission_id同时重复
//
//
//        //     $params['role_permissions']预期结构 [[role_id=>1,permission_id=>1,menu_id=>1],[role_id=>1,permission_id=>2,menu_id=>1]](新增时无role_id,如果有 unset掉)
//        ////     $params['role_menu_id']预期结构 [[role_id=>1,menu_id=>1],[role_id=>1,menu_id=>1]](新增时无role_id,如果有 unset掉)
//        halt($params);
//        unset($params['role_id']);
//
////        这里需要更新角色关联表
//        if ($info->intelligentUpdate($params)) {
//            return $this->success();
//        }
//        return $this->error($info->getMessage());
//
//
//    }


    /**
     * 删除
     * @param $role_id
     * @param Delete $delete
     * @return Response
     */
    public function delete($role_id, Delete $delete): Response
    {

        $model = (new  RoleModel())->findOrEmpty($role_id);
        if ($model->isEmpty()) {
            return $this->error("指定数据不存在");
        }
        if ($model->admins()->count()) {
            return $this->error("有管理员关联这个角色,请先取消关联");
        }
        if ($model->together(["role_permissions", "role_menus", "role_admins"])->delete()) {
            return $this->success("删除成功");
        } else {
            return $this->error($model->getMessage());
        }
    }


    /**
     * 分配菜单
     * @param $roleId
     * @param assignMenu $request
     * @return Response
     */
    public function assignMenu($roleId, assignMenu $request): Response
    {

        // 2. 获取并验证数据
        $data = $request->post();


        try {
            (new PermissionService())->assignMenuToRole(
                $roleId,
                $data['menu_ids'],
                $data['button_permissions'] ?? []
            );

            // 4. 返回成功
            return $this->success([
                'role_id' => $roleId,
                'assigned_menus' => $data['menu_ids'],
                'updated_at' => time()
            ], '权限分配成功');

        } catch (Throwable $e) {
            // 5. 异常处理
            return $this->error('分配失败: ' . $e->getMessage(), 500);
        }


    }
}