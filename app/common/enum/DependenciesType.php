<?php

namespace app\common\enum;

use app\common\trait\EnumTrait;

Enum DependenciesType : string
{
    case Required = "REQUIRED";
    case Optional = "OPTIONAL";

    use EnumTrait;




    public static function getList(): array
    {
        return [
            [
                'key'=>self::Required,
                'value'=>"必备",
            ],
            [
                'key'=>self::Optional,
                'value'=>"可选",
            ],
        ];


    }

}