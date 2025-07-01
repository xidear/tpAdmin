<?php


namespace app\controller;

use app\common\BaseController;
use app\common\BaseRequest;
use app\model\Admin as AdminModel;
use think\facade\Event;
use think\Response;

class Admin extends BaseController
{
    /**
     * 更新管理员信息
     * @param BaseRequest $request
     * @param int $adminId 管理员 ID
     * @return Response
     */
    public function updateAdminInfo(BaseRequest $request,int $adminId): \think\Response
    {
        $admin = (new \app\model\Admin)->findOrEmpty($adminId);
        if (!$admin->isEmpty()) {
            return $this->error('管理员不存在');
        }
//        TODO: 验证数据
//        TODO: 更新管理员信息
        $admin->save();
        // 触发事件更新缓存
        Event::trigger('AdminInfoUpdated', $adminId);
        return $this->success([], '更新成功');
    }

//    TODO: 更改状态仍然需要触发事件更新缓存

}