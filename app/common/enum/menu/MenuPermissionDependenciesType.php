<?php

namespace app\common\enum\menu;

use app\common\trait\EnumTrait;

Enum MenuPermissionDependenciesType : string
{
    case Required = "REQUIRED";
    case Optional = "OPTIONAL";

    use EnumTrait;




    public static function getList(): array
    {
        return [
            [
                'key'=>self::Required->value,
                'value'=>"必备",
            ],
            [
                'key'=>self::Optional->value,
                'value'=>"可选",
            ],
        ];


    }

}