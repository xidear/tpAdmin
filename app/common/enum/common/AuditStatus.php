<?php
namespace app\common\enum;

use app\common\trait\EnumTrait;

enum AuditStatus: int
{
    case Normal = 1;
    case Disabled = 2;
    case Pending = 3;


    use EnumTrait;


    public static function getList(): array
    {
        return [
            [
                'key' => self::Normal->value,
                'value' => "已审核通过",
            ],
            [
                'key' => self::Disabled->value,
                'value' => "审核不通过",
            ],

            [
                'key' => self::Pending->value,
                'value' => "待审核",
            ],
        ];


    }

}