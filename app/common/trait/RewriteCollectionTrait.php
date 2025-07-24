<?php
// app/common/trait/RewriteCollectionTrait.php
namespace app\common\trait;

use app\common\collection\TpCollection;
use think\model\Collection; // 注意这里必须是model下的Collection

trait RewriteCollectionTrait
{
    /**
     * 严格遵循父类返回类型：think\model\Collection
     * @param iterable $collection
     * @param string|null $resultSetType
     * @return \think\model\Collection
     */
    public function toCollection(iterable $collection = [], string $resultSetType = null): \think\model\Collection
    {
        $resultSetType = $resultSetType ?: $this->resultSetType;

        if ($resultSetType && str_contains($resultSetType, '\\')) {
            // 确保自定义集合继承自think\model\Collection
            return new $resultSetType($collection);
        } else {
            // TreeCollection必须继承think\model\Collection
            return new TpCollection($collection);
        }
    }
}