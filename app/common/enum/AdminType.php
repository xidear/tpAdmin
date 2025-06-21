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
                'key'=>self::Admin,
                'value'=>"后台用户",
            ],
            [
                'key'=>self::User,
                'value'=>"小程序用户",
            ],
        ];


    }

}