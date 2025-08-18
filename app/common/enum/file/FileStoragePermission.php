<?php
namespace app\common\enum\file;

use app\common\trait\EnumTrait;

enum FileStoragePermission : string
{
    case Public = 'public';
    case Private = 'private';

    use EnumTrait;

    public static function getList(): array
    {
        return [
            [
                'key' => self::Public->value,
                'value' => '公开',
            ],
            [
                'key' => self::Private->value,
                'value' => '私有',
            ],
        ];
    }
}
