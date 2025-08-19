<?php

namespace app\common\enum;

use app\common\trait\EnumTrait;

Enum SuccessOrFail : int
{
    case Success = 1;
    case Fail = 2;

    use EnumTrait;




    public static function getList(): array
    {
        return [
            [
                'key'=>self::Success->value,
                'value'=>"成功",
            ],
            [
                'key'=>self::Fail->value,
                'value'=>"失败",
            ],
        ];


    }

}