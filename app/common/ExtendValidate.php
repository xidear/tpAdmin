<?php

namespace app\common;

use think\facade\Db;
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

    /**
     * 验证权限依赖中是否包含至少一个必选权限
     */
    /**
     * 验证权限依赖（如果存在，则检查格式和有效性）
     */
    /**
     * 验证权限依赖（如果存在，则检查格式和有效性）
     */
    /**
     * 验证权限依赖（如果存在，则检查格式和有效性，并确保每个条目完整）
     */
    protected function checkRequiredDependency($value, $rule, $data = [], $field = '')
    {
        // 如果字段不存在或为空数组，直接通过
        if (!isset($value) || empty($value)) {
            return true;
        }

        // 验证是否为数组
        if (!is_array($value)) {
            return '权限依赖必须为数组';
        }

        // 遍历检查每个依赖项（强制要求所有字段存在且合法）
        foreach ($value as $index => $item) {
            // 验证每个依赖项是否为数组
            if (!is_array($item)) {
                return "权限依赖项 #{$index} 格式错误";
            }

            // 验证permission_id（必需）
            if (!isset($item['permission_id'])) {
                return "权限依赖项 #{$index} 缺少permission_id字段";
            }
            if (!is_numeric($item['permission_id']) || $item['permission_id'] <= 0) {
                return "权限依赖项 #{$index} 的权限ID无效";
            }

            // 验证type（必需）
            if (!isset($item['type'])) {
                return "权限依赖项 #{$index} 缺少type字段";
            }
            if (!in_array($item['type'], ['REQUIRED', 'OPTIONAL'])) {
                return "权限依赖项 #{$index} 的类型无效";
            }

            // 验证permission_type（必需）
            if (!isset($item['permission_type'])) {
                return "权限依赖项 #{$index} 缺少permission_type字段";
            }
            if (!in_array($item['permission_type'], ['button', 'data', 'filter'])) {
                return "权限依赖项 #{$index} 的权限类型无效";
            }
        }

        return true;
    }

    /**
     * 新增：exists 验证方法（检查数据库中是否存在记录）
     * 用法：'parent_id' => 'exists:menu,menu_id'
     */
    protected function exists($value, $rule, $data = [], $field = ''): bool
    {
        // 解析规则：exists:table,field（默认field为id）
        list($table, $field) = explode(',', $rule . ',id');
        // 检查数据库中是否存在记录
        return Db::name($table)->where($field, $value)->count() > 0;
    }



}