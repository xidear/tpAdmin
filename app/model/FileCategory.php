<?php

namespace app\model;

use app\common\BaseModel;

/**
 * 文件分类模型
 * @property int $category_id 分类ID
 * @property string $name 分类名称
 * @property int $parent_id 父分类ID
 * @property int $sort 排序
 * @property string $description 描述
 * @property int $status 状态：1启用，2禁用
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class FileCategory extends BaseModel
{
    protected $pk = 'category_id';
    protected $table = 'file_category';

    /**
     * 关联子分类
     */
    public function children()
    {
        return $this->hasMany(FileCategory::class, 'parent_id', 'category_id');
    }

    /**
     * 关联父分类
     */
    public function parent()
    {
        return $this->belongsTo(FileCategory::class, 'parent_id', 'category_id');
    }

    /**
     * 关联文件
     */
    public function files()
    {
        return $this->hasMany(File::class, 'category_id', 'category_id');
    }
}
