<?php
namespace app\common\enum;

use app\common\trait\EnumTrait;

enum MenuPermissionDependencies: string
{
    case Required='REQUIRED';
    case Optional='OPTIONAL';

    use EnumTrait;


    public static function getList(): array
    {
        return [
            [
                'key' => self::Required,
                'value' => "必备的",
            ],
            [
                'key' => self::Optional,
                'value' => "可选的",
            ],
        ];


    }

}