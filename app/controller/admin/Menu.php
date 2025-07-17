<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\common\BaseRequest;
use app\model\Permission;
use app\Request;
use app\request\admin\login\Delete;
use app\request\admin\menu\Read;
use think\Response;

class Menu extends BaseController
{


    /**
     * @param BaseRequest $request
     * @return Response
     */
    public function tree(BaseRequest $request): Response
    {
        $menus= \app\model\Menu::getMenuTree($request);

        return $this->success($menus);
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
     * 读取
     * @param $id
     * @param Read $request
     * @return Response
     */
    public function read($id, Read $request): Response{

        $id=request()->param('id');
        $menu= (new \app\model\Menu)->with([
'requiredPermission','dependencies'
        ])->findOrEmpty($id);

//        $permissions=(new Permission())->column("permission_id,name,is_public");


            return $this->success($menu);
    }


}