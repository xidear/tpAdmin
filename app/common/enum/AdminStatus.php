<?php
namespace app\common\enum;

use app\common\trait\EnumTrait;

Enum AdminStatus : int
{
    case Normal = 1;
    case Disabled = 2;

    use EnumTrait;




    public static function getList(): array
    {
        return [
            [
                'key'=>self::Normal,
                'value'=>"允许登录",
            ],
            [
                'key'=>self::Disabled,
                'value'=>"已禁用",
            ],
        ];


    }

}