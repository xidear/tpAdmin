<?php


namespace app\controller\admin;

use app\common\BaseController;
use app\model\Admin as AdminModel;
use app\request\admin\admin\BatchDelete;
use app\request\admin\admin\Create;
use app\request\admin\admin\Delete;
use app\request\admin\admin\Edit;
use app\request\admin\admin\Read;
use think\Response;

class Admin extends BaseController
{


    /**
     * @return Response
     */
    public function index(): Response
    {
        $list = (new AdminModel())->fetchPaginated();
        return $this->success($list);
    }


    /**
     * @param $admin_id
     * @param Read $read
     * @return Response
     */
    public function read($admin_id, Read $read): Response
    {
        return $this->success((new AdminModel())->fetchOne($admin_id));
    }


    /**
     * @param Create $create
     * @return Response
     */
    public function create(Create $create): Response
    {
        $params = $create->param();
        return $this->success((new AdminModel())->fetchOneOrCreate($params));
    }


    /**
     * @param $admin_id
     * @param Edit $edit
     * @return Response
     */
    public function update($admin_id, Edit $edit): Response
    {
        $params = $this->request->param();
        $info = (new AdminModel())->fetchOne($admin_id);
        if ($info->isEmpty()) {
            return $this->error("未找到指定数据");
        }
//        这里需要更新角色关联表
        if ($info->intelligentUpdate($params)) {
            // 更新成功后清除缓存
            AdminModel::clearCache($admin_id);
            return $this->success($info, "编辑成功");
        }
        return $this->error("编辑失败");


    }


    /**
     * @param BatchDelete $delete
     * @return Response
     */
    public function batchDelete(BatchDelete $delete): Response
    {
        $ids = $delete->delete("ids/a");
        $model = new AdminModel();
        if (in_array($model->getSuperAdminId(), $ids)) {
            return $this->error("超级管理员禁止删除");
        }
        if ($model->batchDeleteWithRelation($ids, ["admin_role"])) {
            return $this->success("删除成功");
        } else {
            return $this->error($model->getMessage());
        }
    }


    /**
     * @param $admin_id
     * @param Delete $delete
     * @return Response
     */
    public function delete($admin_id,Delete $delete): Response
    {
        $ids=[$admin_id];
        $model = new AdminModel();
        if (in_array($model->getSuperAdminId(), $ids)) {
            return $this->error("超级管理员禁止删除");
        }
        if ($model->batchDeleteWithRelation($ids, ["admin_role"])) {
            return $this->success("删除成功");
        } else {
            return $this->error($model->getMessage());
        }
    }


}