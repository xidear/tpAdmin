<?php

namespace app\model;

use app\common\BaseModel;

/**
 * 文件标签模型
 * @property int $tag_id 标签ID
 * @property string $name 标签名称
 * @property int $sort 排序
 * @property string $description 描述
 * @property int $status 状态：1启用，2禁用
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class FileTag extends BaseModel
{
    protected $pk = 'tag_id';
    protected $table = 'file_tag';

    /**
     * 关联文件（多对多）
     */
    public function files()
    {
        return $this->belongsToMany(File::class, FileTagRelation::class, 'tag_id', 'file_id');
    }
}
