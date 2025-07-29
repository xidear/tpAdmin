<?php
namespace app\common\enum;

use app\common\trait\EnumTrait;

Enum AdminType : string
{
    case Admin = 'admin';
    case User = 'user';

    use EnumTrait;




    public static function getList(): array
    {
        return [
            [
                'key'=>self::Admin->value,
                'value'=>"后台用户",
            ],
            [
                'key'=>self::User->value,
                'value'=>"小程序用户",
            ],
        ];


    }

}