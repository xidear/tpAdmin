<?php

namespace app\controller\admin;

use app\common\service\RegionCacheService;
use app\model\Region as RegionModel;
use think\facade\Log;

class Region extends \app\common\BaseController
{
    protected $regionCacheService;
    
    public function __construct(RegionCacheService $regionCacheService)
    {
        parent::__construct(app());
        $this->regionCacheService = $regionCacheService;
    }
    
    /**
     * 获取地区树形结构
     * @param int $level 指定返回的层级深度，默认为0表示所有层级
     * @param bool $force_refresh 是否强制刷新缓存
     * @return \think\Response
     */
    public function tree()
    {
        $level = $this->request->get('level', 0); // 0表示所有层级，1-4表示指定层级
        $forceRefresh = $this->request->get('force_refresh', 0) == 1;
        
        try {
            if ($level == 0) {
                // 获取所有层级
                $data = $this->regionCacheService->getAllLevelRegions($forceRefresh);
            } else {
                // 获取指定层级
                $data = $this->regionCacheService->getRegionsByLevel($level, $forceRefresh);
            }
            
             return $this->success($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    
  
    /**
     * 刷新缓存（异步队列处理）
     * @return \think\Response
     */
    public function refreshCache()
    {
        try {
      



            
            // 使用Redis队列
            $queue = \think\facade\Queue::push(\app\job\RefreshRegionCache::class, [], 'region_cache');
            
            if (!$queue) {
                return $this->error('队列推送失败');
            }
            
            return $this->success([
                'job_id' => $queue,
                'message' => '缓存刷新任务已提交到队列，正在异步处理中'
            ], '缓存刷新任务已提交');
            
        } catch (\Exception $e) {
            Log::error('提交缓存刷新队列任务失败：' . $e->getMessage());
            return $this->error('提交缓存刷新任务失败');
        }
    }


    
    
 
    
    /**
     * 获取单个地区详情
     * @param int $region_id
     * @return \think\Response
     */
    public function read(int $region_id): \think\Response
    {
        $region = RegionModel::with('parentRegion')->findOrEmpty($region_id);
        if ($region->isEmpty()) {
            return $this->error("未找到指定地区");
        }
        return $this->success($region);
    }
    
    /**
     * 创建新地区
     * @return \think\Response
     */
    public function create(): \think\Response
    {
        $data = $this->request->param();
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
            
            // 模型的afterSave方法会自动触发事件，这里不需要重复触发
            
            return $this->success($region);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    /**
     * 更新地区信息
     * @param int $region_id
     * @return 	\think\Response
     */
    public function update(int $region_id): 	\think\Response
    {
        $data = $this->request->param();
        $region = RegionModel::findOrEmpty($region_id);

        if ($region->isEmpty()) {
            return $this->error("未找到指定地区");
        }

        // 如果父ID变更，需要更新路径
        $parentIdChanged = isset($data['parent_id']) && $data['parent_id'] != $region->parent_id;

        // 验证父ID不能为自己或自己的子级
        if ($parentIdChanged && isset($data['parent_id'])) {
            // 不能选择自己作为父级
            if ($data['parent_id'] == $region_id) {
                return $this->error("不能选择自己作为父级地区");
            }
            
            // 不能选择自己的子级作为父级
            $allDescendantIds = $region->getAllDescendantIds($region_id);
            if (in_array($data['parent_id'], $allDescendantIds)) {
                return $this->error("不能选择自己的子级作为父级地区");
            }
        }

        try {
            $region->save($data);

            // 如果父ID变更，更新路径及子地区路径
            if ($parentIdChanged) {
                $region->updateRegionPath($region_id);
            }
            
            // 模型的afterSave方法会自动触发事件，这里不需要重复触发
            
            return $this->success($region);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    /**
     * 删除地区
     * @param int $region_id
     * @return \think\Response
     */
    public function delete(int $region_id): \think\Response
    {
        $region = RegionModel::findOrEmpty($region_id);
        if ($region->isEmpty()) {
            return $this->error("未找到指定地区");
        }

        if (!$region->deleteRecursive()) {
            return $this->error($region->getError());
        }
        
        // 模型的deleteRecursive方法会自动触发事件，这里不需要重复触发
        
        return $this->success([]);
    }
    
    /**
     * 恢复地区
     * @param int $region_id
     * @return \think\Response
     */
    public function restore(int $region_id): \think\Response
    {
        $region = new RegionModel();
        $result = $region->restoreRegion($region_id);

        if (!$result) {
            return $this->error($region->getError());
        }
        
        // 模型的restoreRegion方法会自动触发事件，这里不需要重复触发
        
        return $this->success([]);
    }
    
    /**
     * 合并地区
     * @return \think\Response
     */
    public function merge(): \think\Response
    {
        $data = $this->request->param();
        $region = new RegionModel();

        $result = $region->mergeRegions($data['target_region_id'], $data['source_region_ids']);

        if (!$result) {
            return $this->error($region->getError());
        }
        
        // 模型的mergeRegions方法会自动触发事件，这里不需要重复触发
        
        return $this->success([]);
    }
    
    /**
     * 拆分地区
     * @return \think\Response
     */
    public function split(): \think\Response
    {
        $data = $this->request->param();
        $region = new RegionModel();

        $result = $region->splitRegion($data['parent_region_id'], $data['new_regions']);

        if (!$result) {
            return $this->error($region->getError());
        }
        
        // 模型的splitRegion方法会自动触发事件，这里不需要重复触发
        
        return $this->success([]);
    }
    
   
}