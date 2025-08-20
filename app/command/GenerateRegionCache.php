<?php
declare (strict_types = 1);

namespace app\command;

use app\common\service\RegionCacheService;
use think\App;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Cache;
use app\model\Region;

class GenerateRegionCache extends Command
{
    protected function configure(): void
    {
        $this->setName('region:cache')
             ->setDescription('生成所有层级的地区数据缓存');
    }

    protected function execute(Input $input, Output $output): int
    {
        $startTime = microtime(true);
        $output->writeln('开始生成地区数据缓存...');
        
        try {
            // 实例化缓存服务
            $cacheService = new RegionCacheService();
            
            // 执行缓存刷新
            $result = $cacheService->executeCacheRefresh();

            $totalTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($result) {
                $output->writeln("<info>地区数据缓存生成完成！总耗时: {$totalTime}ms</info>");
                
                if ($totalTime > 30000) {
                    $output->writeln('<warning>警告：生成时间超过30秒，需要进一步优化</warning>');
                }
                
                return 0;
            }
            
            $output->writeln('<error>生成缓存失败</error>');
            return 1;
            
        } catch (\Exception $e) {
            $output->writeln('<error>生成缓存失败：' . $e->getMessage() . '</error>');
            return 1;
        }
    }
    
    /**
     * 清除所有地区缓存
     */
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
     * 生成所有层级的缓存 - 高效算法，避免使用toTree
     */
    private function generateAllLevelsCache(): void
    {
        // 一次性获取所有地区数据，避免多次查询
        $queryStart = microtime(true);
        try {
            $allRegions = (new \app\model\Region)->where('deleted_at', null)
                ->field('region_id,parent_id,name,type,level,code,snum,path')
                ->order('level asc, snum asc, region_id asc')
                ->select()
                ->toArray();

            $queryTime = round((microtime(true) - $queryStart) * 1000, 2);
            echo "查询所有地区数据耗时: {$queryTime}ms\n";

            // 构建索引数组，提高查找效率
            $indexStart = microtime(true);
            $regionIndex = [];
            foreach ($allRegions as $region) {
                $regionIndex[$region['region_id']] = $region;
            }
            $indexTime = round((microtime(true) - $indexStart) * 1000, 2);
            echo "构建索引数组耗时: {$indexTime}ms\n";

            // 高效构建树形结构
            $treeStart = microtime(true);
            $treeData = $this->buildTreeEfficiently($allRegions, $regionIndex);
            $treeTime = round((microtime(true) - $treeStart) * 1000, 2);
            echo "构建树形结构耗时: {$treeTime}ms\n";

            // 批量设置hasChildren属性
            $hasChildrenStart = microtime(true);
            $this->batchSetHasChildren($treeData);
            $hasChildrenTime = round((microtime(true) - $hasChildrenStart) * 1000, 2);
            echo "设置hasChildren属性耗时: {$hasChildrenTime}ms\n";

            // 保存完整层级缓存
            $cacheStart = microtime(true);
            Cache::set('region_all_levels', $treeData);
            echo "保存完整层级缓存耗时: " . round((microtime(true) - $cacheStart) * 1000, 2) . "ms\n";

            // 生成三级缓存
            $threeLevelStart = microtime(true);
            $threeLevelData = $this->generateLevelCache(3, $allRegions, $regionIndex);
            Cache::set('region_level_3', $threeLevelData);
            echo "生成三级缓存耗时: " . round((microtime(true) - $threeLevelStart) * 1000, 2) . "ms\n";

            // 生成四级缓存
            $fourLevelStart = microtime(true);
            $fourLevelData = $this->generateLevelCache(4, $allRegions, $regionIndex);
            Cache::set('region_level_4', $fourLevelData);
            echo "生成四级缓存耗时: " . round((microtime(true) - $fourLevelStart) * 1000, 2) . "ms\n";
        } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
            echo "获取数据出错:".$e->getMessage();
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
     * 高效构建树形结构 - 避免使用toTree方法
     * @param array $allRegions 所有地区数据
     * @param array $regionIndex 地区索引
     * @return array
     */
    private function buildTreeEfficiently(array $allRegions, array $regionIndex): array
    {
        $tree = [];
        
        // 按层级处理，确保父节点先被处理
        foreach ($allRegions as $region) {
            if ($region['parent_id'] == 0) {
                // 顶级节点
                $tree[] = &$regionIndex[$region['region_id']];
            } else {
                // 子节点
                if (isset($regionIndex[$region['parent_id']])) {
                    if (!isset($regionIndex[$region['parent_id']]['children'])) {
                        $regionIndex[$region['parent_id']]['children'] = [];
                    }
                    $regionIndex[$region['parent_id']]['children'][] = &$regionIndex[$region['region_id']];
                }
            }
        }
        
        return $tree;
    }
    
    /**
     * 批量设置hasChildren属性 - 使用预计算的方式
     * @param array $regions
     */
    private function batchSetHasChildren(array &$regions): void
    {
        foreach ($regions as &$region) {
            // 直接根据children数组判断是否有子节点，避免额外查询
            $region['hasChildren'] = !empty($region['children']);
            
            if (!empty($region['children'])) {
                $this->batchSetHasChildren($region['children']);
            }
        }
    }
}