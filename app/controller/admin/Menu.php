<?php

namespace app\controller\admin;

use app\common\BaseController;
use think\Response;

class Menu extends BaseController
{


    /**
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->success([]);
    }


}