<?php
namespace app\common\enum;

use app\common\trait\EnumTrait;

enum MenuPermissionPermissionType: string
{
    case Data='data';
    case Filter='filter';
    case Button='button';

    use EnumTrait;


    public static function getList(): array
    {
        return [
            [
                'key' => self::Data->value,
                'value' => "列表数据",
            ],
            [
                'key' => self::Filter->value,
                'value' => "过滤支持项",
            ],
            [
                'key' => self::Button->value,
                'value' => "权限按钮",
            ],
        ];


    }

}