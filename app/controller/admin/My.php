<?php

namespace app\controller\admin;


use app\common\BaseController;
use app\common\BaseRequest;
use app\common\enum\Status;
use app\common\enum\YesOrNo;
use app\model\SystemConfig;
use app\request\admin\my\changePassword;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Response;

class My extends BaseController
{

    public function getBaseInfo(BaseRequest $request): Response
    {
//        获取当前登录用户的基本数据
        $adminInfo=request()->admin->toArray();
        $adminInfo['is_super']=request()->admin->isSuper()?YesOrNo::Yes:YesOrNo::No;
        $adminInfo['role_name_list']=request()->admin->roles()->column("name");

        $baseInfo['admin']=$adminInfo;



        $baseInfo['system']=SystemConfig::getCacheValues(["site_name","admin_logo","phone","company_name","company_url","icp"]);
        return  $this->success($baseInfo);
    }

    /**
     * 修改密码
     * @param changepassword $request
     * @return response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function changePassword(changePassword $request): Response
    {
        $data = request()->post();
        $admin=(new  \app\model\Admin)->findOrFail(request()->adminId);
        if (!$admin->changePassword($data['old_password'],$data['password']) ) {
            return $this->error($admin->getMessage());
        }
        return $this->success([],"修改成功");
    }

    /**
     * 获取菜单
     * @param baserequest $request
     * @return response
     */
    public function getMenu(baseRequest $request): \think\Response
    {
        $menus= \app\model\Menu::getUserMenuTree(request()->adminId,$request);
        return $this->success($menus);
    }

    /**
     * 获取权限按钮
     * @param BaseRequest $request
     * @return Response
     */
    public function getButtons(BaseRequest $request): \think\Response{
        $buttons= \app\model\Menu::getUserButtons(request()->adminId);
        return $this->success($buttons);
    }
}