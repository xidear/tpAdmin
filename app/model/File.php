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
 * @property string $mime_type
 * @property string  $storage_permission
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
}