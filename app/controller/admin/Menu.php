<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\common\BaseRequest;
use app\model\RolePermission;
use app\request\admin\menu\Create;
use app\request\admin\menu\Delete;
use app\request\admin\menu\Read;
use app\request\admin\menu\Update;
use Exception;
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

    /**
     * 更新菜单
     * @param $menu_id
     * @param Update $request
     * @return Response
     */
    public function update($menu_id, Update $request): Response
    {
        $data = $request->param();

        $menu = (new \app\model\Menu)->findOrEmpty($menu_id);
        if ($menu->isEmpty()) {
            return $this->error("没找到数据");
        }

        $menu->startTrans();
        try {
            $oldPermissionIds = $menu->dependencies()->column('permission_id');
            if (!empty($oldPermissionIds)) {
                // 提取新权限ID列表（使用array_column）
                $newPermissionIds = !empty($data['dependencies']) ? array_column($data['dependencies'], 'permission_id') : [];
                if (!empty($newPermissionIds)) {
                    // 计算需删除的权限ID（使用array_diff）
                    $removedPermissionIds = array_diff($oldPermissionIds, $newPermissionIds);
                    // 批量删除角色关联（使用事务）
                    if (!empty($removedPermissionIds)) {
                        RolePermission::where('menu_id', $menu_id)
                            ->whereIn('permission_id', $removedPermissionIds)
                            ->delete();
                    }
                }
            }
            $menu->dependencies()->delete();
            if (!empty($data['dependencies'])) {
                $menu->dependencies()->saveAll($data['dependencies']);
                unset($data['dependencies']);
            }
            $menu->save($data);
            $menu->commit();
        } catch (Exception $e) {
            $menu->rollback();
            return $this->error($e->getMessage());
        }
        return $this->success($data);


    }

    /**
     * 删除
     * @param $menu_id
     * @param Delete $request
     * @return Response
     */
    public function delete($menu_id,Delete $request): Response
    {

//        这里仅支持单个删除

        $menu = (new \app\model\Menu)->findOrEmpty($menu_id,);
        if ($menu->isEmpty()) {
            return $this->error("找不到指定的数据");
        }
        if (!$menu->deleteRecursive()) {
            return $this->error($menu->getError());
        }
        return $this->success([]);
    }

    /**
     * 创建菜单
     * @param Create $request
     * @return Response
     */
    public function create(Create $request): Response
    {
        $data = $request->param();
        $menu = (new \app\model\Menu);
        $menu->startTrans();
        try {
            $menu->save($data);
            if (!empty($data['dependencies'])) {
                $menu->dependencies()->saveAll($data['dependencies']);
            }
            $menu->commit();
        } catch (Exception $e) {
            $menu->rollback();
            return $this->error($e->getMessage());
        }
        return $this->success($data);


    }


}