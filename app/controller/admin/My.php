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
     * 更新个人信息
     * @param BaseRequest $request
     * @return Response
     */
    public function updateProfile(BaseRequest $request): Response
    {
        $data = request()->post();
        
        // 只允许修改特定字段
        $allowedFields = ['username', 'nick_name', 'avatar'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            return $this->error('没有可更新的数据');
        }
        
        // 验证用户名唯一性（排除当前用户）
        if (isset($updateData['username'])) {
            $existingAdmin = \app\model\Admin::where('username', $updateData['username'])
                ->where('admin_id', '!=', request()->adminId)
                ->find();
            if ($existingAdmin) {
                return $this->error('用户名已存在');
            }
        }
        
        // 更新当前登录用户信息
        $admin = \app\model\Admin::findOrFail(request()->adminId);
        $result = $admin->save($updateData);
        
        if ($result) {
            // 清除缓存
            \app\model\Admin::clearCache(request()->adminId);
            return $this->success([], '个人信息更新成功');
        } else {
            return $this->error('更新失败');
        }
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