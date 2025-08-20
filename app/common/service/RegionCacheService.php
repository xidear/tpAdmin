<?php

namespace app\common\service;

use app\common\BaseService;
use app\common\trait\BaseTrait;
use app\model\Region;
use think\facade\Cache;
use think\facade\Log;
use think\facade\Queue;

class RegionCacheService
{

    use BaseTrait;
    // 分层缓存键名前缀
    const string CACHE_KEY_PREFIX = 'region_level_';
    
    // 3层缓存键名
    const string THREE_LEVEL_CACHE_KEY = 'region_three_levels';
    
    // 缓存时间
    const int|float CACHE_TTL = 86400*365; // 1年
    
    /**
     * 获取指定层级的地区数据
     * @param int $parentId 父级ID，0表示顶级
     * @param bool $forceRefresh 是否强制刷新
     * @return array
     */
    public function getRegionsByParent(int $parentId = 0, bool $forceRefresh = false): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $parentId;
        
//        try {
            if (!$forceRefresh) {
                $cachedData = Cache::get($cacheKey);
                if ($cachedData !== null) {
                    return $cachedData;
                }
            }
            
            // 从数据库获取数据
            $regions = (new \app\model\Region)->where('parent_id', $parentId)
                ->where('deleted_at', null)
                ->order('snum asc, region_id asc')
                ->select()
                ->toArray();
            
            // 为每个地区添加是否有子节点的标记
            foreach ($regions as &$region) {
                $region['hasChildren'] = $this->hasChildren($region['region_id']);
            }
            
            // 保存到缓存
            Cache::set($cacheKey, $regions, self::CACHE_TTL);
            
            return $regions;
            
//        } catch (\Exception $e) {
//            Log::error('获取地区数据失败：' . $e->getMessage());
//            return [];
//        }
    }
    
    /**
     * 获取前3层完整地区数据（省份、城市、区县）
     * @param bool $forceRefresh 是否强制刷新
     * @return array
     */
    public function getThreeLevelRegions(bool $forceRefresh = false): array
    {
        $cacheKey = self::THREE_LEVEL_CACHE_KEY;
        
        try {
            if (!$forceRefresh) {
                $cachedData = Cache::get($cacheKey);
                if ($cachedData !== null) {
                    return $cachedData;
                }
            }
            
            // 获取第1层：省份
            $provinces = Region::where('parent_id', 0)
                ->where('deleted_at', null)
                ->order('snum asc, region_id asc')
                ->select()
                ->toArray();
            
            $result = [];
            
            // 递归加载前3层
            foreach ($provinces as &$province) {
                $province['hasChildren'] = $this->hasChildren($province['region_id']);
                $province['children'] = $this->loadChildrenLevel($province['region_id'], 2, 3);
                $result[] = $province;
            }
            
            // 保存到缓存
            Cache::set($cacheKey, $result, self::CACHE_TTL);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('获取前3层地区数据失败：' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 递归加载子层级数据
     * @param int $parentId 父级ID
     * @param int $currentLevel 当前层级
     * @param int $maxLevel 最大层级
     * @return array
     */
    private function loadChildrenLevel(int $parentId, int $currentLevel, int $maxLevel): array
    {
        if ($currentLevel > $maxLevel) {
            return [];
        }
        
        try {
            $children = Region::where('parent_id', $parentId)
                ->where('deleted_at', null)
                ->order('snum asc, region_id asc')
                ->select()
                ->toArray();
            
            $result = [];
            
            foreach ($children as &$child) {
                $child['hasChildren'] = $this->hasChildren($child['region_id']);
                
                // 如果当前层级小于最大层级，继续加载子级
                if ($currentLevel < $maxLevel) {
                    $child['children'] = $this->loadChildrenLevel($child['region_id'], $currentLevel + 1, $maxLevel);
                } else {
                    $child['children'] = [];
                }
                
                $result[] = $child;
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('加载子层级失败：' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 检查指定地区是否有子节点
     * @param int $regionId
     * @return bool
     */
    private function hasChildren(int $regionId): bool
    {
        try {
            return Region::where('parent_id', $regionId)
                ->where('deleted_at', null)
                ->count() > 0;
        } catch (\Exception $e) {
            Log::error('检查子节点失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 清除指定层级的缓存
     * @param int|null $parentId 父级ID，为null时清除所有缓存
     * @return bool
     */
    public function clearCache(?int $parentId = null): bool
    {
        try {
            if ($parentId === null) {
                // 清除所有地区缓存
                Cache::tag('region_cache')->clear();
                // 清除3层缓存
            } else {
                // 清除指定层级的缓存
                $cacheKey = self::CACHE_KEY_PREFIX . $parentId;
                Cache::delete($cacheKey);
                // 清除3层缓存（因为可能影响结构）
            }
            Cache::delete(self::THREE_LEVEL_CACHE_KEY);
            return true;
        } catch (\Exception $e) {
            Log::error('清除缓存失败：' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取缓存状态
     * @return array
     */
    public function getCacheStatus(): array
    {
        try {
            // 获取顶级地区缓存状态
            $topCacheKey = self::CACHE_KEY_PREFIX . '0';
            $topCache = Cache::get($topCacheKey);
            
            // 获取3层缓存状态
            $threeLevelCache = Cache::get(self::THREE_LEVEL_CACHE_KEY);
            
            return [
                'top_level_cached' => $topCache !== null,
                'top_level_count' => $topCache ? count($topCache) : 0,
                'three_level_cached' => $threeLevelCache !== null,
                'three_level_count' => $threeLevelCache ? count($threeLevelCache) : 0,
                'cache_ttl' => self::CACHE_TTL,
                'cache_strategy' => 'hybrid_cache'
            ];
        } catch (\Exception $e) {
            Log::error('获取缓存状态失败：' . $e->getMessage());
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 重新生成缓存（兼容旧接口）
     * @param bool $async 是否异步（此参数在新方案中已无意义）
     * @return array
     */
    public function regenerateCache(bool $async = false): array
    {
        // 清除所有缓存
        $this->clearCache();

        // 返回顶级地区数据
        return $this->getRegionsByParent(0);
    }
    
    /**
     * 获取所有层级地区数据
     * @param bool $forceRefresh 是否强制刷新
     * @return array
     */
    public function getAllLevelRegions(bool $forceRefresh = false): array
    {
        
        $cacheKey = 'region_all_levels';
    
        try {
            if (!$forceRefresh) {
                $cachedData = Cache::get($cacheKey);
              
                if ($cachedData !== null) {
                    return $cachedData;
                }
            }
            // 如果缓存不存在，生成缓存
            $this->generateAllLevelsCache();
            
            // 重新获取缓存数据
            $cachedData = Cache::get($cacheKey);
            if ($cachedData === null) {
                Log::error('生成缓存后仍无法获取缓存数据');
                return [];
            }
            
            return $cachedData;
            
        } catch (\Exception $e) {
            Log::error('获取所有层级地区数据失败：' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 执行缓存刷新 - 替代regenerateCache方法
     * @return bool
     */
    public function executeCacheRefresh(): bool
    {
        try {
            // 清除所有缓存
            $this->clearAllCache();

            // 生成所有层级缓存
            $this->generateAllLevelsCache();

            return true;
        } catch (\Exception $e) {
            Log::error('执行缓存刷新失败：' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 清除所有地区缓存
     */
    private function clearAllCache(): void
    {
        Cache::delete('region_all_levels');
        Cache::delete('region_level_3');
        Cache::delete('region_level_4');
        Cache::delete('region_three_levels'); // 清除旧的三级缓存
        Cache::tag('region_cache')->clear();
    }
    
    /**
     *
     * 生成所有层级的缓存 - 高效算法，避免使用toTree
     */
    private function generateAllLevelsCache(): void
    {
        // 一次性获取所有地区数据，避免多次查询
        $allRegions = Region::where('deleted_at', null)
             ->field('region_id,parent_id,name,type,level,code,snum,path')
            ->order('level asc, snum asc, region_id asc')
            ->select()
            ->toArray();
        
        // 构建索引数组，提高查找效率
        $regionIndex = [];
        foreach ($allRegions as $region) {
            $regionIndex[$region['region_id']] = $region;
        }

        // 高效构建树形结构
        $treeData = $this->buildTreeEfficiently($allRegions, $regionIndex);
        
        // 批量设置hasChildren属性
        $this->batchSetHasChildren($treeData);
        
        // 保存完整层级缓存
        Cache::set('region_all_levels', $treeData, self::CACHE_TTL);
        
        // 生成三级缓存
        $threeLevelData = $this->generateLevelCache(3, $allRegions, $regionIndex);
        Cache::set('region_level_3', $threeLevelData, self::CACHE_TTL);
        
        // 生成四级缓存
        $fourLevelData = $this->generateLevelCache(4, $allRegions, $regionIndex);
        Cache::set('region_level_4', $fourLevelData, self::CACHE_TTL);
    }

        /**
     * 高效构建树形结构
     * @param array $allRegions 所有地区数据
     * @param array $regionIndex 地区索引
     * @return array
     */
    private function buildTreeEfficiently(array $allRegions, array $regionIndex): array
    {
        $tree = [];
        
        // 首先找到所有顶级节点（parent_id = 0）
        foreach ($allRegions as $region) {
            if ($region['parent_id'] == 0) {
                $tree[] = $region;
            }
        }
        
        // 递归构建子节点
        foreach ($tree as &$node) {
            $this->buildChildren($node, $allRegions, $regionIndex);
        }
        
        return $tree;
    }
    
    /**
     * 递归构建子节点
     * @param array &$parentNode 父节点
     * @param array $allRegions 所有地区数据
     * @param array $regionIndex 地区索引
     */
    private function buildChildren(array &$parentNode, array $allRegions, array $regionIndex): void
    {
        $parentNode['children'] = [];
        
        foreach ($allRegions as $region) {
            if ($region['parent_id'] == $parentNode['region_id']) {
                $child = $region;
                $this->buildChildren($child, $allRegions, $regionIndex);
                $parentNode['children'][] = $child;
            }
        }
    }
    
    /**
     * 批量设置hasChildren属性
     * @param array &$treeData 树形数据
     */
    private function batchSetHasChildren(array &$treeData): void
    {
        foreach ($treeData as &$node) {
            $node['hasChildren'] = !empty($node['children']);
            if (!empty($node['children'])) {
                $this->batchSetHasChildren($node['children']);
            }
        }
    }
    
    
    /**
     * 生成指定层级的缓存
     * @param int $level 层级深度
     * @param array $allRegions 所有地区数据
     * @param array $regionIndex 地区索引
     * @return array
     */
    private function generateLevelCache(int $level, array $allRegions, array $regionIndex): array
    {
        // 获取第1层：省份
        $provinces = [];
        foreach ($allRegions as $region) {
            if ($region['parent_id'] == 0) {
                $provinces[] = $region;
            }
        }
        
        $result = [];
        
        // 递归构建指定层级
        foreach ($provinces as &$province) {
            $province['hasChildren'] = $this->hasChildrenById($province['region_id'], $regionIndex);
            $province['children'] = $this->buildLevelChildren($province['region_id'], 2, $level, $allRegions, $regionIndex);
            $result[] = $province;
        }
        
        return $result;
    }
    
    /**
     * 根据ID检查是否有子节点
     * @param int $regionId
     * @param array $regionIndex
     * @return bool
     */
    private function hasChildrenById(int $regionId, array $regionIndex): bool
    {
        foreach ($regionIndex as $region) {
            if ($region['parent_id'] == $regionId) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * 构建指定层级的子节点
     * @param int $parentId 父级ID
     * @param int $currentLevel 当前层级
     * @param int $maxLevel 最大层级
     * @param array $allRegions 所有地区数据
     * @param array $regionIndex 地区索引
     * @return array
     */
    private function buildLevelChildren(int $parentId, int $currentLevel, int $maxLevel, array $allRegions, array $regionIndex): array
    {
        if ($currentLevel > $maxLevel) {
            return [];
        }
        
        $result = [];
        
        foreach ($allRegions as $region) {
            if ($region['parent_id'] == $parentId) {
                $child = $region;
                $child['hasChildren'] = $this->hasChildrenById($child['region_id'], $regionIndex);
                
                if ($currentLevel < $maxLevel) {
                    $child['children'] = $this->buildLevelChildren($child['region_id'], $currentLevel + 1, $maxLevel, $allRegions, $regionIndex);
                } else {
                    $child['children'] = [];
                }
                
                $result[] = $child;
            }
        }
        
        return $result;
    }
    
    /**
     * 获取指定层级的地区数据
     * @param int $level 层级深度（1-4）
     * @param bool $forceRefresh 是否强制刷新
     * @return array
     */
    public function getRegionsByLevel(int $level, bool $forceRefresh = false): array
    {
        if ($level < 1 || $level > 4) {
            throw new \InvalidArgumentException('层级参数必须在1-4之间');
        }
        
        $cacheKey = 'region_level_' . $level;
        
        try {
            if (!$forceRefresh) {
                $cachedData = Cache::get($cacheKey);
                if ($cachedData !== null) {
                    return $cachedData;
                }
            }
            
            // 获取第1层：省份
            $provinces = Region::where('parent_id', 0)
                ->where('deleted_at', null)
                ->order('snum asc, region_id asc')
                ->select()
                ->toArray();
            
            $result = [];
            
            // 递归加载指定层级
            foreach ($provinces as &$province) {
                $province['hasChildren'] = $this->hasChildren($province['region_id']);
                $province['children'] = $this->loadChildrenLevel($province['region_id'], 2, $level);
                $result[] = $province;
            }
            
            // 保存到缓存
            Cache::set($cacheKey, $result, self::CACHE_TTL);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('获取指定层级地区数据失败：' . $e->getMessage());
            return [];
        }
    }
}