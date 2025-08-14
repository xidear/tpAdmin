<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\common\service\PermissionService;
use app\model\Permission as PermissionModel;
use app\request\admin\permission\BatchDelete;
use app\request\admin\permission\Create;
use app\request\admin\permission\Delete;
use app\request\admin\permission\Edit;
use app\request\admin\permission\Read;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Response;


class Permission extends BaseController
{


    /**
     * @return Response
     */
    public function index(): Response
    {

        $list = (new PermissionModel())->fetchData();
        return $this->success($list);
    }

    /**
     * 同步
     * @return Response
     */
    public function sync(): Response
    {
        $service=new PermissionService();
        try {
            $result = $service->sync("adminapi", true);
            if (!$result){
                return $this->error($service->getMessage());
            }
        } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
            return $this->error($e->getMessage());


        }

        return $this->success();
    }




    /**
     * @param $permission_id
     * @param Read $read
     * @return Response
     */
    public function read($permission_id, Read $read): Response
    {
        return $this->success((new PermissionModel())->fetchOne($permission_id));
    }


    /**
     * @param Create $create
     * @return Response
     */
    public function create( Create $create): Response
    {
        $params = $this->request->param();
        return $this->success((new PermissionModel())->fetchOneOrCreate($params));
    }


    /**
     * @param $permission_id
     * @param Edit $edit
     * @return Response
     */
    public function update( $permission_id,Edit $edit): Response
    {
        $params = $this->request->param();
        $info=(new PermissionModel())->fetchOne($permission_id);
        if ($info->isEmpty()){
            return $this->error("未找到指定数据");
        }
        if ($info->intelligentUpdate($params)){
            return $this->success($info,"编辑成功");
        }
        return $this->error("编辑失败");


    }


    /**
     * @param $permission_id
     * @param Delete $delete
     * @return Response
     */
    public function delete($permission_id,Delete $delete): Response
    {
        $model=new PermissionModel();

        $ids=[$permission_id];
        if ($model->batchDeleteWithRelation($ids,["menu_dependencies"])){
            return $this->success("删除成功");
        }else{
            return $this->error($model->getMessage());
        }
    }


    /**
     * @param BatchDelete $delete
     * @return Response
     */
    public function batchDelete(BatchDelete $delete): Response
    {
        $ids=$delete->delete("ids/a");
        $model=new PermissionModel();
        if ($model->batchDeleteWithRelation($ids,["menu_dependencies"])){
            return $this->success("删除成功");
        }else{
            return $this->error($model->getMessage());
        }
    }








}