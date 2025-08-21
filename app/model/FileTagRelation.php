<?php

namespace app\model;

use app\common\BaseModel;

/**
 * 文件标签关联模型
 * @property int $id 关联ID
 * @property int $file_id 文件ID
 * @property int $tag_id 标签ID
 * @property string $created_at 创建时间
 */
class FileTagRelation extends BaseModel
{
    protected $table = 'file_tag_relation';
    
    // 不需要主键自增
    public $autoIncrement = false;
    
    /**
     * 关联文件
     */
    public function file()
    {
        return $this->belongsTo(File::class, 'file_id', 'file_id');
    }
    
    /**
     * 关联标签
     */
    public function tag()
    {
        return $this->belongsTo(FileTag::class, 'tag_id', 'tag_id');
    }
}
