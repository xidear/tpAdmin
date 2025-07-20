<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\common\BaseRequest;
use app\request\admin\menu\Delete;
use app\request\admin\menu\Read;
use app\request\admin\menu\Update;
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
     * @param $menu_id
     * @param Read $request
     * @return Response
     */
    public function read($menu_id, Read $request): Response
    {

        $menu = (new \app\model\Menu)->with([
           'dependencies.permission'
        ])->findOrEmpty($menu_id);

        return $this->success($menu);
    }

    public function update($menu_id,Update $request): Response{
        $data=$request->param();

        $menu=(new \app\model\Menu)->findOrEmpty($menu_id);
        if ($menu->isEmpty()) {
            return $this->error("没找到数据");
        }

        $menu->startTrans();
        try {
            $menu->dependencies()->delete();
            if (!empty($data['dependencies'])) {
                $menu->dependencies()->saveAll($data['dependencies']);
                unset($data['dependencies']);
            }
            $menu->save($data);
            $menu->commit();
        }catch (\Exception $e){
            $menu->rollback();
            return $this->error($e->getMessage());
        }
        return  $this->success($data);



    }


}