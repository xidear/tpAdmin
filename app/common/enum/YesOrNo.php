<?php

namespace app\common\enum;

use app\common\trait\EnumTrait;

Enum YesOrNo : int
{
    case Yes = 1;
    case No = 2;

    use EnumTrait;




    public static function getList(): array
    {
        return [
            [
                'key'=>self::Yes,
                'value'=>"是",
            ],
            [
                'key'=>self::No,
                'value'=>"否",
            ],
        ];


    }

}