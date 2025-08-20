<?php
namespace app\common\enum\file;

use app\common\trait\EnumTrait;

enum FileStatus : string
{
    case Active = 'active';
    case Deleted = 'deleted';
    case Uploading = 'uploading';
    case Expired = 'expired';

    use EnumTrait;

    public static function getList(): array
    {
        return [
            [
                'key' => self::Active->value,
                'value' => '正常',
            ],
            [
                'key' => self::Deleted->value,
                'value' => '已删除',
            ],
            [
                'key' => self::Uploading->value,
                'value' => '上传中',
            ],
            [
                'key' => self::Expired->value,
                'value' => '已过期',
            ],
        ];
    }
}
