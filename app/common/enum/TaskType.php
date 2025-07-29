<?php

namespace app\common\enum;

use app\common\trait\EnumTrait;

enum TaskType: int
{
    case COMMAND = 1; // 命令行
    case URL = 2;     // URL请求
    case PHP = 3;     // PHP方法

    use EnumTrait;

    public static function getList(): array
    {
        return [
            ['key' => self::COMMAND->value, 'value' => '命令行'],
            ['key' => self::URL->value, 'value' => 'URL请求'],
            ['key' => self::PHP->value, 'value' => 'PHP方法'],
        ];
    }
}
