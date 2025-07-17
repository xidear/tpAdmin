<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\model\Permission as PermissionModel;
use app\request\admin\permission\Create;
use app\request\admin\permission\Delete;
use app\request\admin\permission\Edit;
use app\request\admin\permission\Read;
use think\Response;


class Permission extends BaseController
{


    /**
     * @return Response
     */
    public function index(): Response
    {
        $list = (new PermissionModel())->fetchPaginated();
        return $this->success($list);
    }


    /**
     * @param $id
     * @param Read $read
     * @return Response
     */
    public function read($id, Read $read): Response
    {
        return $this->success((new PermissionModel())->fetchOne($id));
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
     * @param $id
     * @param Edit $edit
     * @return Response
     */
    public function update( $id,Edit $edit): Response
    {
        $params = $this->request->param();
        $info=(new PermissionModel())->fetchOne($id);
        if ($info->isEmpty()){
            return $this->error("未找到指定数据");
        }
        if ($info->intelligentUpdate($params)){
            return $this->success($info,"编辑成功");
        }
        return $this->error("编辑失败");


    }


    /**
     * @param Delete $delete
     * @return Response
     */
    public function delete(Delete $delete): Response
    {
       $ids=$delete->post("ids/a");


        $model=new PermissionModel();



        if ($model->batchDeleteWithRelation($ids)){
            return $this->success("删除成功");
        }else{
            return $this->error($model->getMessage());
        }



    }






}