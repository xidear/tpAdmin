<?php

namespace app\model;

use app\common\BaseModel;
use think\Model;
use think\model\concern\SoftDelete;

/**
 * @property int $category_id
 * @property string $name
 * @property string $code
 * @property int $parent_id
 * @property int $level
 * @property string $path
 * @property int $sort
 * @property int $status
 * @property string $description
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_type
 */
class ImageCategory extends BaseModel
{
    use SoftDelete;

    // 表名
    protected $name = 'image_category';

    // 主键
    protected $pk = 'category_id';

    // 自动写入时间戳
    protected bool $autoWriteTimestamp = true;

    // 软删除字段
    protected string $deleteTime = 'deleted_at';

    // 字段类型转换
    protected $type = [
        'category_id' => 'integer',
        'parent_id' => 'integer',
        'level' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    // 允许修改的字段
    protected $allowField = [
        'name', 'code', 'parent_id', 'level', 'path', 'sort', 'status', 'description'
    ];

    /**
     * 关联子分类
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'category_id');
    }

    /**
     * 关联父分类
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'category_id');
    }

    /**
     * 关联图片
     */
    public function images()
    {
        return $this->hasMany(File::class, 'category_id', 'category_id');
    }

    /**
     * 获取分类路径
     */
    public function getPathAttr($value)
    {
        if (empty($value)) {
            return $this->category_id;
        }
        return $value;
    }

    /**
     * 设置分类路径
     */
    public function setPathAttr($value)
    {
        if ($this->parent_id > 0) {
            $parent = $this->parent;
            if ($parent) {
                return $parent->path . ',' . $this->category_id;
            }
        }
        return $this->category_id;
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr()
    {
        $statusMap = [
            0 => '禁用',
            1 => '启用'
        ];
        return $statusMap[$this->status] ?? '未知';
    }

    /**
     * 检查是否有子分类
     */
    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    /**
     * 检查是否有图片
     */
    public function hasImages(): bool
    {
        return $this->images()->where('mime_type', 'like', 'image/%')->count() > 0;
    }

    /**
     * 获取所有子分类ID（递归）
     */
    public function getAllChildrenIds(): array
    {
        $ids = [];
        $children = $this->children;
        
        foreach ($children as $child) {
            $ids[] = $child->category_id;
            $ids = array_merge($ids, $child->getAllChildrenIds());
        }
        
        return $ids;
    }

    /**
     * 获取所有父分类ID（递归）
     */
    public function getAllParentIds(): array
    {
        $ids = [];
        $parent = $this->parent;
        
        while ($parent) {
            $ids[] = $parent->category_id;
            $parent = $parent->parent;
        }
        
        return array_reverse($ids);
    }
}
