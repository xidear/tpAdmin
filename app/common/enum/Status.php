<?php

namespace app\common\enum;

use app\common\trait\EnumTrait;

enum Status: int
{
    case DISABLED = 2;  // 禁用
    case ENABLED = 1;   // 启用

    use EnumTrait;

    public static function getList(): array
    {
        return [
            [
                'key' => self::DISABLED->value,
                'value' => '禁用',
                'description' => '状态为禁用'
            ],
            [
                'key' => self::ENABLED->value,
                'value' => '启用',
                'description' => '状态为启用'
            ]
        ];
    }

    /**
     * 获取状态文本
     */
    public function getText(): string
    {
        return match($this) {
            self::DISABLED => '禁用',
            self::ENABLED => '启用',
        };
    }

    /**
     * 获取状态标签类型
     */
    public function getTagType(): string
    {
        return match($this) {
            self::DISABLED => 'danger',
            self::ENABLED => 'success',
        };
    }

    /**
     * 检查是否为启用状态
     */
    public function isEnabled(): bool
    {
        return $this === self::ENABLED;
    }

    /**
     * 检查是否为禁用状态
     */
    public function isDisabled(): bool
    {
        return $this === self::DISABLED;
    }
}