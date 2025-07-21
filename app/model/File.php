<?php
namespace app\model;

use app\common\BaseModel;
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

    // 存储类型常量
    const string STORAGE_LOCAL = 'local';
    const string STORAGE_ALIYUN_OSS = 'aliyun_oss';
    const string STORAGE_QCLOUD_COS = 'qcloud_cos';
    const string STORAGE_AWS_S3 = 'aws_s3';

    // 文件状态常量
    const string STATUS_ACTIVE = 'active';
    const string STATUS_DELETED = 'deleted';
    const string STATUS_UPLOADING = 'uploading';
    const string STATUS_EXPIRED = 'expired';

    // 存储权限常量
    const string PERMISSION_PUBLIC = 'public';
    const string PERMISSION_PRIVATE = 'private';

    // 上传者类型常量
    const string UPLOADER_USER = 'user';
    const string UPLOADER_SYSTEM = 'system';
    const string UPLOADER_ADMIN = 'admin';



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
        return $this->storage_permission === self::PERMISSION_PRIVATE;
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