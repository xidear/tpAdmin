<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\model\Role as RoleModel;
use app\request\admin\admin\Create;
use app\request\admin\admin\Edit;
use app\request\admin\admin\Read;
use app\request\admin\role\assignMenu;
use app\request\admin\role\Delete;
use app\service\PermissionService;
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

        $info=(new RoleModel())
            ->fetchOne($role_id, ['append' => ["menu_tree_with_permission"],
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
//        未完成
//        新增和更新的时候都要考虑 menu 和 permission
        $params = $create->param();
        return $this->success((new RoleModel())->fetchOneOrCreate($params));
    }


    /**
     * @param $role_id
     * @param Edit $edit
     * @return Response
     */
    public function update($role_id, Edit $edit): Response
    {
//        未完成
        $params = $this->request->param();
        $info = (new RoleModel())->fetchOne($role_id);
        if ($info->isEmpty()) {
            return $this->error("未找到指定数据");
        }


        if (!empty($params['role_menus'])) {
            $menuIds = array_column($params['role_menus'], 'id');
        }
        halt($params);

//        这里需要更新角色关联表
        if ($info->intelligentUpdate($params)) {
            return $this->success();
        }
        return $this->error($info->getMessage());


    }


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