<?php

namespace app\common;

use think\Validate;

class ExtendValidate extends Validate
{
    // 密码强度验证规则
    public function password($value): bool
    {

        if (!is_string($value)) {
            return false;
        }

        if (strlen($value) < 8 || strlen($value) > 32) {
            return false;
        }

        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return false;
        }

        $typeCount = 0;
        $typeCount += preg_match('/[0-9]/', $value);         // 数字
        $typeCount += preg_match('/[a-z]/', $value);         // 小写
        $typeCount += preg_match('/[A-Z]/', $value);         // 大写
        $typeCount += preg_match('/[!@#$%^&*()\[\]\-_=+{};:,.<>?\/]/', $value); // 符号

        return $typeCount > 2;

    }


    // 自定义验证方法
    protected function graph($value, $rule, $data = [], $field = ''): false|int
    {
        return preg_match('/^[\x21-\x7E]+$/', $value); // 可打印字符，不含空格
    }

    protected function print($value, $rule, $data = [], $field = ''): false|int
    {
        return preg_match('/^[\x20-\x7E]+$/', $value); // 可打印字符，含空格
    }




}