<?php
namespace app\common\enum\file;

use app\common\trait\EnumTrait;

enum FileStorageType : string
{
    case Local = 'local';
    case AliyunOss = 'aliyun_oss';
    case QcloudCos = 'qcloud_cos';
    case AwsS3 = 'aws_s3';

    use EnumTrait;

    public static function getList(): array
    {
        return [
            [
                'key' => self::Local->value,
                'value' => '本地存储',
            ],
            [
                'key' => self::AliyunOss->value,
                'value' => '阿里云OSS',
            ],
            [
                'key' => self::QcloudCos->value,
                'value' => '腾讯云COS',
            ],
            [
                'key' => self::AwsS3->value,
                'value' => 'AWS S3',
            ],
        ];
    }
}
