<?php

namespace app\controller\admin;


use app\common\BaseController;
use think\Response;

class Index extends BaseController
{


    public function index(){

        $model=new \app\model\Permission();

        $list=$model->paginate();
        return $this->success($list);
    }

    /**
     *
     * @return Response
     */
    public function dashboard(): Response
    {

        return $this->success([
            'title' => "欢迎使用xxx系统"
        ]);

    }


}