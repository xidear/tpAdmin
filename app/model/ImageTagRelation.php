<?php

namespace app\model;

use app\common\BaseModel;
use think\Model;

/**
 * @property int $id
 * @property int $file_id
 * @property int $tag_id
 * @property string $created_at
 * @property string $updated_at
 */
class ImageTagRelation extends BaseModel
{
    // 表名
    protected $name = 'image_tag_relation';

    // 主键
    protected $pk = 'id';

    // 自动写入时间戳
    protected bool $autoWriteTimestamp = true;

    // 字段类型转换
    protected $type = [
        'id' => 'integer',
        'file_id' => 'integer',
        'tag_id' => 'integer',
    ];

    // 允许修改的字段
    protected $allowField = [
        'file_id', 'tag_id'
    ];

    /**
     * 关联图片
     */
    public function image()
    {
        return $this->belongsTo(File::class, 'file_id', 'file_id');
    }

    /**
     * 关联标签
     */
    public function tag()
    {
        return $this->belongsTo(ImageTag::class, 'tag_id', 'tag_id');
    }

    /**
     * 批量添加标签关联
     */
    public static function batchAdd($fileId, array $tagIds)
    {
        $data = [];
        foreach ($tagIds as $tagId) {
            $data[] = [
                'file_id' => $fileId,
                'tag_id' => $tagId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }
        
        if (!empty($data)) {
            return self::insertAll($data);
        }
        
        return true;
    }

    /**
     * 更新图片标签关联
     */
    public static function updateImageTags($fileId, array $tagIds)
    {
        // 删除旧的关联
        self::where('file_id', $fileId)->delete();
        
        // 添加新的关联
        return self::batchAdd($fileId, $tagIds);
    }

    /**
     * 获取图片的所有标签
     */
    public static function getImageTags($fileId)
    {
        return self::with('tag')
                  ->where('file_id', $fileId)
                  ->select()
                  ->map(function ($item) {
                      return $item->tag;
                  });
    }

    /**
     * 获取标签下的所有图片
     */
    public static function getTagImages($tagId)
    {
        return self::with('image')
                  ->where('tag_id', $tagId)
                  ->select()
                  ->map(function ($item) {
                      return $item->image;
                  });
    }
}
