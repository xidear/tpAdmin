<?php

namespace app\model;

use app\common\BaseModel;
use think\Model;
use think\model\concern\SoftDelete;

/**
 * @property int $tag_id
 * @property string $name
 * @property string $color
 * @property int $count
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_type
 */
class ImageTag extends BaseModel
{
    use SoftDelete;

    // 表名
    protected $name = 'image_tag';

    // 主键
    protected $pk = 'tag_id';

    // 自动写入时间戳
    protected bool $autoWriteTimestamp = true;

    // 软删除字段
    protected string $deleteTime = 'deleted_at';

    // 字段类型转换
    protected $type = [
        'tag_id' => 'integer',
        'count' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    // 允许修改的字段
    protected $allowField = [
        'name', 'color'
    ];

    /**
     * 关联图片（通过中间表）
     */
    public function images()
    {
        return $this->belongsToMany(File::class, ImageTagRelation::class, 'tag_id', 'file_id');
    }

    /**
     * 获取标签颜色（如果没有设置则返回默认颜色）
     */
    public function getColorAttr($value)
    {
        if (empty($value)) {
            // 根据标签名称生成一个固定的颜色
            $colors = [
                '#409EFF', '#67C23A', '#E6A23C', '#F56C6C', '#909399',
                '#36B3A3', '#9B59B6', '#E74C3C', '#F39C12', '#1ABC9C'
            ];
            $index = crc32($this->name) % count($colors);
            return $colors[$index];
        }
        return $value;
    }

    /**
     * 更新标签使用次数
     */
    public function updateCount()
    {
        $count = ImageTagRelation::where('tag_id', $this->tag_id)->count();
        $this->count = $count;
        $this->save();
    }

    /**
     * 获取标签样式
     */
    public function getTagStyle()
    {
        return [
            'background-color' => $this->color,
            'color' => $this->getContrastColor($this->color),
            'border-color' => $this->color
        ];
    }

    /**
     * 获取对比色（用于文字颜色）
     */
    private function getContrastColor($hexColor)
    {
        // 移除#号
        $hexColor = ltrim($hexColor, '#');
        
        // 转换为RGB
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));
        
        // 计算亮度
        $brightness = ($r * 299 + $g * 587 + $b * 114) / 1000;
        
        // 根据亮度返回黑色或白色
        return $brightness > 128 ? '#000000' : '#FFFFFF';
    }
}
