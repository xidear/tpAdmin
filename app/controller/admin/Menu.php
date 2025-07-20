<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\common\BaseRequest;
use app\request\admin\menu\Delete;
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
        $menus = \app\model\Menu::getAllMenuTree();

        return $this->success($menus);
    }

    /**
     * 删除
     * @param Delete $request
     * @return Response
     */
    public function delete(Delete $request): Response
    {

//        这里仅支持单个删除
        $id = request()->param('ids')[0];
        $menu = (new \app\model\Menu)->findOrEmpty($id);
        if ($menu->isEmpty()) {
            return $this->error("找不到指定的数据");
        }
        if (!$menu->deleteRecursive($menu->getKey())) {
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
    public function read($id, Read $request): Response
    {

        $id = request()->param('id');
        $menu = (new \app\model\Menu)->with([
           'dependencies.permission'
        ])->findOrEmpty($id);

        return $this->success($menu);
    }


}