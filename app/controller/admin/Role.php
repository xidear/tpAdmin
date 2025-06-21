<?php

namespace app\controller\admin;

use app\model\Role as RoleModel;
use app\request\admin\role\assignMenu;
use app\service\PermissionService;
use think\Response;

class Role extends \app\common\BaseController
{
    // 角色列表
    public function index(): \think\Response
    {
        $roles = (new \app\model\Role)->with('permissions')->selectPage();
        return $this->success(["list" => $roles]);
    }

    /**
     * 分配菜单
     * @param $roleId
     * @param assignMenu $request
     * @return Response
     */
    public function assignMenu($roleId,assignMenu $request): Response
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

        } catch (\Throwable $e) {
            // 5. 异常处理
            return $this->error('分配失败: ' . $e->getMessage(), 500);
        }


    }
}