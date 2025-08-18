<?php

namespace app\model;

use app\common\BaseModel;
use app\common\trait\TreeTrait;
use app\event\RegionChanged;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\facade\Event;
use think\facade\Log;
use think\model\concern\SoftDelete;
use think\model\relation\BelongsTo;
use think\model\relation\HasMany;

/**
 * @property int $region_id 地区ID
 * @property int $parent_id 父级ID
 * @property string $name 地区名称
 * @property string $type 地区类型
 * @property int $level 层级
 * @property string $code 地区编码
 * @property int $snum 排序号
 * @property string $path 路径
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $deleted_at 删除时间
 */
class Region extends BaseModel
{
    protected $pk = 'region_id';
    protected bool $autoWriteTimestamp = true;
    protected string $table='region';
    
    use SoftDelete, TreeTrait;

    /**
     * 初始化树形结构配置
     */
    protected function initialize()
    {
        parent::initialize();
        
        // 设置树形结构配置
        $this->setTreeConfig([
            'parentKey' => 'parent_id',
            'primaryKey' => 'region_id',
            'pathKey' => 'path',
            'levelKey' => 'level',
            'nameKey' => 'name',
            'childrenKey' => 'children',
            'pathSeparator' => '/',
            'sortKey' => 'snum',
            'statusKey' => 'status',
            'deletedAtKey' => 'deleted_at',
        ]);
    }
    
    /**
     * 创建/更新地区后触发事件
     */
    public function afterSave()
    {
        parent::afterSave();
        
        // 判断是创建还是更新操作
        $type = $this->wasRecentlyCreated ? RegionChanged::TYPE_CREATED : RegionChanged::TYPE_UPDATED;
        
        // 触发地区变更事件 - 统一使用简化的事件触发
        Event::trigger(RegionChanged::class, new RegionChanged(
            $type,
            $this->region_id
        ));
    }
    
    /**
     * 递归删除指定ID的地区及子级
     * @param int $regionId
     * @return bool
     */
    public function deleteRecursive(int $regionId = 0): bool
    {
        if (empty($regionId)) {
            $regionId = $this->getKey();
        }
        if (empty($regionId)) {
            return $this->false("ID缺失");
        }

        $this->startTrans();
        try {
            // 获取所有后代地区ID（包括自身）
            $allRegionIds = $this->getAllDescendantIds($regionId);

            // 执行软删除
            $result = self::destroy($allRegionIds);

            if ($result === false) {
                throw new Exception("删除地区失败");
            }

            $this->commit();
            
            // 触发地区变更事件 - 统一使用简化的事件触发
            Event::trigger(RegionChanged::class, new RegionChanged(
                RegionChanged::TYPE_DELETED,
                $regionId,
                $allRegionIds
            ));
            
            return true;
        } catch (Exception $e) {
            $this->rollback();
            return $this->false($e->getMessage());
        }
    }
    
    /**
     * 恢复被删除的地区
     * @param int $regionId
     * @return bool
     */
    public function restoreRegion(int $regionId): bool
    {
        $this->startTrans();
        try {
            // 获取所有后代地区ID（包括自身）
            $allRegionIds = $this->getAllDescendantIds($regionId);

            // 恢复删除
            $result = self::onlyTrashed()
                ->whereIn('region_id', $allRegionIds)
                ->update(['deleted_at' => null]);

            if ($result === false) {
                throw new Exception("恢复地区失败");
            }

            $this->commit();
            
            // 触发地区变更事件 - 统一使用简化的事件触发
            Event::trigger(RegionChanged::class, new RegionChanged(
                RegionChanged::TYPE_RESTORED,
                $regionId,
                $allRegionIds
            ));
            
            return true;
        } catch (Exception $e) {
            $this->rollback();
            return $this->false($e->getMessage());
        }
    }
    
    /**
     * 合并地区
     * @param int $targetRegionId 目标地区ID
     * @param array $sourceRegionIds 被合并的地区ID数组
     * @return bool
     */
    public function mergeRegions(int $targetRegionId, array $sourceRegionIds): bool
    {
        if (empty($targetRegionId) || empty($sourceRegionIds)) {
            return $this->false("参数不完整");
        }

        // 检查目标地区是否存在
        $targetRegion = self::find($targetRegionId);
        if (!$targetRegion) {
            return $this->false("目标地区不存在");
        }

        $this->startTrans();
        try {
            foreach ($sourceRegionIds as $regionId) {
                if ($regionId == $targetRegionId) {
                    continue;
                }

                // 获取被合并地区的子地区
                $children = $this->where('parent_id', $regionId)->select();
                foreach ($children as $child) {
                    // 更新子地区的父ID为目标地区ID
                    $child->parent_id = $targetRegionId;
                    // 更新路径
                    $child->path = str_replace("/{$regionId}/", "/{$targetRegionId}/", $child->path);
                    $child->save();
                }

                // 标记被合并地区为删除
                $region = self::find($regionId);
                $region->delete();
            }

            $this->commit();
            
            // 触发地区变更事件 - 统一使用简化的事件触发
            Event::trigger(RegionChanged::class, new RegionChanged(
                RegionChanged::TYPE_MERGED,
                $targetRegionId,
                $sourceRegionIds
            ));
            
            return true;
        } catch (Exception $e) {
            $this->rollback();
            return $this->false($e->getMessage());
        }
    }

    /**
     * 按父ID获取地区树形结构（支持懒加载）- 高性能方法，保留不合并
     * @param int $parentId 父级ID，默认为0（顶级）
     * @param bool $recursive 是否递归加载所有子节点
     * @return array
     */
    public static function getRegionTreeByParentId(int $parentId = 0, bool $recursive = false): array
    {
        try {
            // 基础查询：获取指定父ID的直接子节点
            $query = self::where('parent_id', $parentId)
                ->where('deleted_at', null)
                ->order('snum asc, region_id asc');

            $regions = $query->select()->toArray() ?? [];

            // 如果需要递归加载所有子节点，递归调用自身
            if ($recursive && !empty($regions)) {
                foreach ($regions as &$region) {
                    $region['children'] = self::getRegionTreeByParentId($region['region_id'], true);
                }
            } else {
                // 非递归模式下，只标记是否有子节点（用于前端判断是否显示展开按钮）
                foreach ($regions as &$region) {
                    $hasChildren = self::where('parent_id', $region['region_id'])
                            ->where('deleted_at', null)
                            ->count() > 0; // 使用 count() 判断记录是否存在
                    $region['hasChildren'] = $hasChildren; // 标记是否有子节点
                }
            }

            return $regions;
        } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
            Log::error('获取地区树形结构失败：' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取树形结构 - 高性能方法，保留不合并
     * @param int $level
     * @return array
     */
    public static function getAllRegionTree($level=3): array
    {
        try {
            return   self::field('region_id,parent_id,name')->where("level","<=",$level)
                ->order('snum asc')
                ->select()->toTree();
        } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
            return [];
        }
    }

    /**
     * 获取父级地区
     * @return BelongsTo
     */
    public function parentRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'parent_id', 'region_id');
    }

    /**
     * 获取子级地区
     * @return HasMany
     */
    public function childrenRegions(): HasMany
    {
        return $this->hasMany(Region::class, 'parent_id', 'region_id');
    }

    /**
     * 拆分地区 - 创建新的子地区
     * @param int $parentRegionId 父地区ID
     * @param array $newRegions 新地区数据数组
     * @return bool
     */
    public function splitRegion(int $parentRegionId, array $newRegions): bool
    {
        if (empty($parentRegionId) || empty($newRegions)) {
            return $this->false("参数不完整");
        }

        // 检查父地区是否存在
        $parentRegion = self::find($parentRegionId);
        if (!$parentRegion) {
            return $this->false("父地区不存在");
        }

        $this->startTrans();
        try {
            $createdRegionIds = [];
            foreach ($newRegions as $regionData) {
                $region = new self();
                $region->parent_id = $parentRegionId;
                $region->name = $regionData['name'];
                $region->type = $regionData['type'] ?? $parentRegion->type;
                $region->level = $parentRegion->level + 1;
                $region->code = $regionData['code'] ?? '';
                $region->snum = $regionData['snum'] ?? 0;
                // 构建路径
                $region->path = rtrim($parentRegion->path, '/') . "/{$region->region_id}/";
                $region->save();
                $createdRegionIds[] = $region->region_id;
            }

            $this->commit();
            
            // 触发地区变更事件 - 统一使用简化的事件触发
            Event::trigger(RegionChanged::class, new RegionChanged(
                RegionChanged::TYPE_SPLIT,
                $parentRegionId,
                $createdRegionIds
            ));
            
            return true;
        } catch (Exception $e) {
            $this->rollback();
            return $this->false($e->getMessage());
        }
    }

    /**
     * 更新地区路径
     * @param int $regionId
     * @return bool
     */
    public function updateRegionPath(int $regionId): bool
    {
        return $this->updateNodePath($regionId);
    }
}