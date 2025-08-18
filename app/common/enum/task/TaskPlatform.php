<?php

namespace app\common\enum;

use app\common\trait\EnumTrait;

enum TaskPlatform: int
{
    case ALL = 3;       // 全部
    case LINUX = 1;     // Linux
    case WINDOWS = 2;   // Windows

    use EnumTrait;

    public static function getList(): array
    {
        return [
            ['key' => self::ALL->value, 'value' => '全部'],
            ['key' => self::LINUX->value, 'value' => 'Linux'],
            ['key' => self::WINDOWS->value, 'value' => 'Windows'],
        ];
    }
}
