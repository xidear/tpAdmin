<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\model\Permission;
use app\request\admin\login\Delete;
use app\request\admin\login\Read;
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

    /**
     * 删除
     * @param Delete $request
     * @return Response
     */
    public function delete(Delete $request): Response{

        $id=request()->param('id');
        $menu= (new \app\model\Menu)->findOrEmpty($id);
        if ($menu->isEmpty()){
            return $this->success([]);
        }
        if (!$menu->deleteRecursive($menu->getKey())){
            return $this->error($menu->getError());
        }
        return $this->success([]);
    }


    /**
     * 删除
     * @param Read $request
     * @return Response
     */
    public function read(Read $request): Response{

        $id=request()->param('id');
        $menu= (new \app\model\Menu)->with([
'requiredPermission','dependencies'
        ])->findOrEmpty($id);

        $permissions=(new Permission())->column("permission_id,name,is_public");
            return $this->success($menu);
    }


}