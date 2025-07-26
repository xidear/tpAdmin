<?php
// 应用公共文件


use JetBrains\PhpStorm\NoReturn;

if (!function_exists('debug')) {
    /**
     * 调试变量并且中断输出
     * @param mixed $vars 调试变量或者信息
     */
    #[NoReturn] function debug(...$vars): void
    {
        var_dump(...$vars);
        die();
    }
}