<?php
namespace app\common\enum\file;

use app\common\trait\EnumTrait;

enum FileUploaderType : string
{
    case User = 'user';
    case System = 'system';
    case Admin = 'admin';

    use EnumTrait;

    public static function getList(): array
    {
        return [
            [
                'key' => self::User->value,
                'value' => '用户',
            ],
            [
                'key' => self::System->value,
                'value' => '系统',
            ],
            [
                'key' => self::Admin->value,
                'value' => '管理员',
            ],
        ];
    }
}
