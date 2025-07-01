<?php

namespace app\controller\admin;


use app\common\BaseController;
use app\common\BaseRequest;
use app\common\enum\Code;
use app\model\AdminRole;
use app\request\admin\my\changePassword;
use app\service\JwtService;
use think\Response;

class My extends BaseController
{


    /**
     * 修改密码
     * @param changePassword $request
     * @return Response
     */
    public function changePassword(changePassword $request): Response
    {
        $data = request()->post();



        if (!password_verify($data['old_password'],$request->admin->password)) {
            return $this->error("原密码错误");
        }


        $newPassword = password_hash($data['password'],PASSWORD_DEFAULT);

        if (!$request->admin->save(["password" => $newPassword])) {
            return $this->error($request->admin->getError());
        }
        return $this->success([],"修改成功");

    }

    /**
     * 获取菜单
     * @param BaseRequest $request
     * @return Response
     */
    public function getMenu(BaseRequest $request): \think\Response
    {

        $menus= \app\model\Menu::getUserMenuTree($request->adminId);

        return $this->success($menus);
    }

    public function getButtons(BaseRequest $request): \think\Response{
        $menus= \app\model\Menu::getUserButtons($request->adminId);

        return $this->success($menus);
    }
}