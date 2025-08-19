<?php
namespace app\common\enum\admin;

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
                'key'=>self::Normal->value,
                'value'=>"允许登录",
            ],
            [
                'key'=>self::Disabled->value,
                'value'=>"已禁用",
            ],
        ];


    }

}