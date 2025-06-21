<?php

namespace app\controller\admin;


use app\common\BaseController;
use think\Response;

class Index extends BaseController
{


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