<?php
namespace app\model;

use app\common\BaseModel;
use app\common\enum\file\FileStorageType;
use app\common\enum\file\FileStatus;
use app\common\enum\file\FileStoragePermission;
use app\common\enum\file\FileUploaderType;
use think\Model;
use think\model\concern\SoftDelete;

/**
 * @property int $file_id
 * @property string $origin_name
 * @property string $file_name
 * @property string $mime_type
 * @property string $url
 * @property int $size
 * @property string $storage_permission
 * @property int $category_id
 * @property string $storage_path
 * @property string $access_domain
 * @property string $uploader_type
 * @property int $uploader_id
 * @property string $storage_type
 * @property string $storage_region
 * @property string $storage_bucket
 * @property string $file_version
 * @property string $status
 * @property string $tags
 * @property int $local_server_id
 * @property string $title
 * @property string $description
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class File extends BaseModel
{
    use SoftDelete;

    // 表名
    protected $name = 'file';

    // 主键
    protected $pk = 'file_id';

    // 自动写入时间戳
    protected bool $autoWriteTimestamp = true;

    // 软删除字段
    protected string $deleteTime = 'deleted_at';

    // 字段类型转换
    protected $type = [
        'file_id' => 'integer',
        'size' => 'integer',
        'category_id' => 'integer',
        'uploader_id' => 'integer',
    ];

    /**
     * 检查文件是否为图片
     * @return bool
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * 检查文件是否为私有
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->storage_permission === FileStoragePermission::Private->value;
    }

    /**
     * 获取文件大小的友好显示
     * @param int $decimals 小数位数
     * @return string
     */
    public function getSizeHuman($decimals = 2)
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $decimals) . ' ' . $units[$pow];
    }

    /**
     * 关联上传者模型
     * @return \think\model\relation\MorphTo
     */
    public function uploader()
    {
        return $this->morphTo();
    }

    /**
     * 关联文件分类
     */
    public function category()
    {
        return $this->belongsTo(FileCategory::class, 'category_id', 'category_id');
    }

    /**
     * 关联文件标签（通过中间表）
     */
    public function tags()
    {
        return $this->belongsToMany(FileTag::class, FileTagRelation::class, 'file_id', 'tag_id');
    }
}