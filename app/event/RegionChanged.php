<?php

namespace app\event;

class RegionChanged
{
    /**
     * 地区变更类型
     */
    public const TYPE_CREATED = 'created';
    public const TYPE_UPDATED = 'updated';
    public const TYPE_DELETED = 'deleted';
    public const TYPE_RESTORED = 'restored';
    public const TYPE_MERGED = 'merged';
    public const TYPE_SPLIT = 'split';
    
    /**
     * @var string 变更类型
     */
    public $type;
    
    /**
     * @var int|null 地区ID
     */
    public $regionId;
    
    /**
     * @var array 相关的地区ID数组
     */
    public $relatedRegionIds;
    
    /**
     * 构造函数
     * @param string $type
     * @param int|null $regionId
     * @param array $relatedRegionIds
     */
    public function __construct(string $type, ?int $regionId = null, array $relatedRegionIds = [])
    {
        $this->type = $type;
        $this->regionId = $regionId;
        $this->relatedRegionIds = $relatedRegionIds;
    }
}