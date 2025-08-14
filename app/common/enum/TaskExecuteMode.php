<?php

namespace app\common\enum;

use app\common\trait\EnumTrait;


enum TaskExecuteMode: int
{
    case LOOP = 1;     // 循环执行
    case ONCE = 2;     // 一次性执行

    use EnumTrait;

    public static function getList(): array
    {
        return [
            ['key' => self::LOOP->value, 'value' => '循环执行'],
            ['key' => self::ONCE->value, 'value' => '一次性执行'],
        ];
    }
}