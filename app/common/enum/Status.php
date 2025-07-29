<?php

namespace app\common\enum;

use app\common\trait\EnumTrait;

Enum Status : int
{
    case Normal = 1;
    case Disabled = 2;

    use EnumTrait;




    public static function getList(): array
    {
        return [
            [
                'key'=>self::Normal->value,
                'value'=>"可用",
            ],
            [
                'key'=>self::Disabled->value,
                'value'=>"已禁用",
            ],
        ];


    }

}