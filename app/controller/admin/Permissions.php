<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\model\Permission;
use app\request\admin\login\Delete;
use app\request\admin\login\Read;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Response;


class Permissions extends BaseController
{


    /**
     * @return Response
     */
    public function index(): Response
    {
        try {
            $list = (new Permission)->select();
        } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
            return $this->error($e->getMessage());
        }
        return $this->success($list);
    }


}