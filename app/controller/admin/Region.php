<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\common\BaseRequest;
use app\model\Region as RegionModel;
use Exception;
use think\Response;

class Region extends BaseController
{
    /**
     * 分级获取地区树形结构（支持懒加载）
     * @param BaseRequest $request
     * @return Response
     */
    public function tree(BaseRequest $request): Response
    {
        // 获取父级ID参数，默认为0（顶级节点）
        $parentId = $request->param('parent_id', 0, 'intval');
        // 获取是否需要递归加载所有子节点（默认只加载直接子节点）
        $recursive = $request->param('recursive', false, 'boolval');

        // 调用模型方法，根据parent_id获取对应层级的树形结构
        $regions = RegionModel::getRegionTreeByParentId($parentId, $recursive);
        return $this->success($regions);
    }


    /**
     * 获取单个地区详情
     * @param int $region_id
     * @param BaseRequest $request
     * @return Response
     */
    public function read(int $region_id, BaseRequest $request): Response
    {
        $region = RegionModel::with('parentRegion')->findOrEmpty($region_id);
        if ($region->isEmpty()) {
            return $this->error("未找到指定地区");
        }
        return $this->success($region);
    }

    /**
     * 创建新地区
     * @param \app\request\admin\region\Create $request
     * @return Response
     */
    public function create(\app\request\admin\region\Create $request): Response
    {
        $data = $request->param();
        $region = new RegionModel();

        // 处理路径
        if (empty($data['parent_id']) || $data['parent_id'] == 0) {
            $data['level'] = 1;
            $data['path'] = "/{$region->region_id}/"; // 临时值，保存后会更新
        } else {
            $parentRegion = RegionModel::find($data['parent_id']);
            if (!$parentRegion) {
                return $this->error("父地区不存在");
            }
            $data['level'] = $parentRegion->level + 1;
            $data['path'] = rtrim($parentRegion->path, '/') . "/{$region->region_id}/"; // 临时值
        }

        try {
            $region->save($data);
            // 更新路径（使用实际ID）
            $region->path = str_replace("{$region->region_id}", $region->region_id, $region->path);
            $region->save();
            return $this->success($region);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 更新地区信息（包括改名）
     * @param int $region_id
     * @param \app\request\admin\region\Update $request
     * @return Response
     */
    public function update(int $region_id, \app\request\admin\region\Update $request): Response
    {
        $data = $request->param();
        $region = RegionModel::findOrEmpty($region_id);

        if ($region->isEmpty()) {
            return $this->error("未找到指定地区");
        }

        // 如果父ID变更，需要更新路径
        $parentIdChanged = isset($data['parent_id']) && $data['parent_id'] != $region->parent_id;

        try {
            $region->save($data);

            // 如果父ID变更，更新路径及子地区路径
            if ($parentIdChanged) {
                $region->updateRegionPath($region_id);
            }

            return $this->success($region);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 删除地区
     * @param int $region_id
     * @param \app\request\admin\region\Delete $request
     * @return Response
     */
    public function delete(int $region_id, \app\request\admin\region\Delete $request): Response
    {
        $region = RegionModel::findOrEmpty($region_id);
        if ($region->isEmpty()) {
            return $this->error("未找到指定地区");
        }

        if (!$region->deleteRecursive()) {
            return $this->error($region->getError());
        }

        return $this->success([]);
    }

    /**
     * 撤销删除（恢复地区）
     * @param int $region_id
     * @param BaseRequest $request
     * @return Response
     */
    public function restore(int $region_id, BaseRequest $request): Response
    {
        $region = new RegionModel();
        $result = $region->restoreRegion($region_id);

        if (!$result) {
            return $this->error($region->getError());
        }

        return $this->success([]);
    }

    /**
     * 合并地区
     * @param \app\request\admin\region\Merge $request
     * @return Response
     */
    public function merge(\app\request\admin\region\Merge $request): Response
    {
        $data = $request->param();
        $region = new RegionModel();

        $result = $region->mergeRegions($data['target_region_id'], $data['source_region_ids']);

        if (!$result) {
            return $this->error($region->getError());
        }

        return $this->success([]);
    }

    /**
     * 拆分地区
     * @param \app\request\admin\region\Split $request
     * @return Response
     */
    public function split(\app\request\admin\region\Split $request): Response
    {
        $data = $request->param();
        $region = new RegionModel();

        $result = $region->splitRegion($data['parent_region_id'], $data['new_regions']);

        if (!$result) {
            return $this->error($region->getError());
        }

        return $this->success([]);
    }

    /**
     * 获取地区子列表
     * @param int $parent_id
     * @param BaseRequest $request
     * @return Response
     */
    public function children(int $parent_id, BaseRequest $request): Response
    {
        $regions = RegionModel::where('parent_id', $parent_id)
            ->order('snum asc')
            ->select();

        return $this->success($regions);
    }
}