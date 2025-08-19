<?php


namespace app\controller\admin;

use app\common\BaseController;
use app\common\enum\task\Status;
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

        $conditions = [];
        // 新增：处理 keyword 参数，转换为 name 字段的模糊查询条件

        if (request()->has('keyword', 'get', true)) {
            $conditions[] = ['username|real_name|nick_name', 'like', "%" . request()->get('keyword') . "%"];
        }

        if (request()->has('not_super', 'get', true) && request()->get('not_super') == 1) {
            $conditions[] = ['admin_id', '<>', (new  AdminModel())->getSuperAdminId()];
        }

        $list = (new AdminModel())->fetchData($conditions);


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

        if ($params['status'] == Status::DISABLED->value) {
            if ($info->getKey() == request()->adminId) {
                return $this->error("不能禁用自己");
            }
            if ($info->isSuper()) {
                return $this->error("不能禁用超级管理员");

            }
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
    public function delete($admin_id, Delete $delete): Response
    {
        $ids = [$admin_id];
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