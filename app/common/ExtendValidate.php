<?php

namespace app\common;

use app\common\enum\MenuPermissionDependenciesType;
use app\common\enum\MenuPermissionPermissionType;
use think\facade\Db;
use think\Validate;

class ExtendValidate extends Validate
{
    // 密码强度验证规则
    public function password($value, $rule, $data = [], $field = ''): bool|string
    {
        if (!is_string($value)) {
            return "{$field}必须为字符串";
        }

        if (strlen($value) < 8 || strlen($value) > 32) {
            return "{$field}长度必须在8-32位之间";
        }

        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return "{$field}只能包含可打印字符（不含特殊符号）";
        }

        $typeCount = 0;
        $typeCount += preg_match('/[0-9]/', $value) ? 1 : 0;         // 数字
        $typeCount += preg_match('/[a-z]/', $value) ? 1 : 0;         // 小写
        $typeCount += preg_match('/[A-Z]/', $value) ? 1 : 0;         // 大写
        $typeCount += preg_match('/[!@#$%^&*()\[\]\-_=+{};:,.<>?\/]/', $value) ? 1 : 0; // 符号

        if ($typeCount <= 2) {
            return "{$field}必须包含至少3种字符类型（数字、大小写字母、特殊符号）";
        }

        return true;
    }

    // 可打印字符，不含空格
    protected function graph($value, $rule, $data = [], $field = ''): bool|string
    {
        if (!preg_match('/^[\x21-\x7E]+$/', $value)) {
            return "{$field}只能包含可打印字符（不含空格）";
        }
        return true;
    }

    // 可打印字符，含空格
    protected function print($value, $rule, $data = [], $field = ''): bool|string
    {
        if (!preg_match('/^[\x20-\x7E]+$/', $value)) {
            return "{$field}只能包含可打印字符（含空格）";
        }
        return true;
    }

    /**
     * 验证权限依赖
     */
    protected function checkRequiredDependency($value, $rule, $data = [], $field = ''): bool|string
    {
        if (!isset($value) || empty($value)) {
            return true;
        }

        if (!is_array($value)) {
            return "{$field}必须为数组";
        }

        foreach ($value as $index => $item) {
            if (!is_array($item)) {
                return "{$field}[{$index}]格式错误，必须为数组";
            }

            if (!isset($item['permission_id'])) {
                return "{$field}[{$index}]缺少permission_id字段";
            }
            if (!is_numeric($item['permission_id']) || $item['permission_id'] <= 0) {
                return "{$field}[{$index}].permission_id必须为有效的正整数";
            }

            if (!isset($item['type'])) {
                return "{$field}[{$index}]缺少type字段";
            }
            if (!in_array($item['type'], MenuPermissionDependenciesType::getKeyList())) {
                $allowedTypes = MenuPermissionDependenciesType::getKeyListString();
                return "{$field}[{$index}].type值无效，必须是{$allowedTypes}";
            }

            if (!isset($item['permission_type'])) {
                return "{$field}[{$index}]缺少permission_type字段";
            }

            if (!in_array($item['permission_type'],MenuPermissionPermissionType::getKeyList() )) {
                $allowedTypes = MenuPermissionPermissionType::getKeyListString();
                return "{$field}[{$index}].permission_type值无效，必须是{$allowedTypes}";
            }
        }

        return true;
    }

    /**
     * 检查数据库中是否存在记录
     * - 'parent_id' => 'exists:menu,menu_id'  // 严格验证，0（含"0"）会被视为无效
     * - 'parent_id' => 'exists:menu,menu_id,true'  // 允许0或"0"通过验证
     */
    protected function exists($value, $rule, $data = [], $field = ''): bool|string
    {
        $params = explode(',', $rule);

        if (count($params) < 2) {
            return "验证规则格式错误，正确格式：exists:表名,字段名[,是否允许0值(true/false)]";
        }

        list($table, $dbField) = $params;
        $allowZero = isset($params[2]) && filter_var($params[2], FILTER_VALIDATE_BOOLEAN);

        // 关键优化：同时判断数值0和字符串"0"（宽松匹配）
        $isZero = ($value === 0 || $value === '0');
        if ($allowZero && $isZero) {
            return true;
        }

        // 非0值时，验证数据库中是否存在记录
        $count = Db::name($table)->where($dbField, $value)->count();
        if ($count <= 0) {
            return "{$field}不存在有效的记录（值：{$value}）";
        }

        return true;
    }


    /**
     * 验证数组项中不允许存在指定字段
     * 用法：'role_menus' => 'noField:role_id'
     */
    protected function noField($value, $rule, $data = [], $field = ''): bool|string
    {
        if (!is_array($value)) {
            return "{$field}必须为数组";
        }

        foreach ($value as $index => $item) {
            if (is_array($item) && isset($item[$rule])) {
                return "{$field}[{$index}]不允许包含{$rule}字段";
            }
        }
        return true;
    }

    /**
     * 验证数组项中指定字段值必须与目标值一致
     * 用法：'role_menus' => 'validFieldValue:role_id,1'
     */
    protected function validFieldValue($value, $rule, $data = [], $field = ''): bool|string
    {
        list($fieldName, $targetValue) = explode(',', $rule);
        if (!is_array($value)) {
            return "{$field}必须为数组";
        }

        foreach ($value as $index => $item) {
            if (!is_array($item)) {
                return "{$field}[{$index}]必须为数组";
            }
            if (isset($item[$fieldName]) && (string)$item[$fieldName] !== (string)$targetValue) {
                return "{$field}[{$index}].{$fieldName}必须等于{$targetValue}（当前值：{$item[$fieldName]}）";
            }
        }
        return true;
    }

    /**
     * 验证数组中指定字段组合是否存在重复
     * 用法：'role_permissions' => 'checkDuplicates:menu_id&permission_id'
     */
    protected function checkDuplicates($value, $rule, $data = [], $field = ''): bool|string
    {
        $fields = explode('&', $rule);
        if (!is_array($value)) {
            return "{$field}必须为数组";
        }

        $seen = [];
        foreach ($value as $index => $item) {
            if (!is_array($item)) {
                return "{$field}[{$index}]必须为数组";
            }
            
            $keyParts = [];
            foreach ($fields as $f) {
                if (!isset($item[$f])) {
                    return "{$field}[{$index}]缺少{$f}字段";
                }
                $keyParts[] = $item[$f];
            }
            $key = implode('_', $keyParts);
            
            if (in_array($key, $seen)) {
                return "{$field}[{$index}]中存在重复的".implode('和', $fields)."组合（{$key}）";
            }
            $seen[] = $key;
        }
        return true;
    }

    /**
     * 验证数组项中指定字段值是否存在于数据库表中
     * 用法：'role_menus' => 'fieldExists:menu_id,menu,menu_id'
     */
    protected function fieldExists($value, $rule, $data = [], $field = ''): bool|string
    {
        list($fieldName, $table, $dbField) = explode(',', $rule . ',');
        $dbField = $dbField ?: $fieldName;
        
        if (!is_array($value)) {
            return "{$field}必须为数组";
        }

        $values = [];
        foreach ($value as $index => $item) {
            if (!is_array($item)) {
                return "{$field}[{$index}]必须为数组";
            }
            if (!isset($item[$fieldName])) {
                return "{$field}[{$index}]缺少{$fieldName}字段";
            }
            // 确保值是字符串或整数
            $val = $item[$fieldName];
            if (!is_scalar($val) || is_bool($val)) {
                return "{$field}[{$index}].{$fieldName}必须是字符串或整数（当前值：" . gettype($val) . "）";
            }
            $values[$index] = $val;
        }
        
        if (empty($values)) {
            return true;
        }

        // 查询数据库并过滤非字符串/整数的值
        $existsValues = Db::name($table)->whereIn($dbField, $values)->column($dbField);

        $filteredValues = [];
        foreach ($existsValues as $val) {
            if (is_scalar($val) && !is_bool($val)) {
                $filteredValues[] = $val;
            }
        }
        $existsValues = array_flip($filteredValues);
        
        foreach ($values as $index => $val) {
            if (!isset($existsValues[$val])) {
                return "{$field}[{$index}].{$fieldName}不存在有效记录（值：{$val}）";
            }
        }
        
        return true;
    }

    /**
     * 验证数组项中两个字段的组合是否存在于数据库表中
     * 用法：'role_permissions' => 'fieldPairExists:menu_id&permission_id,menu_permission_dependency'
     */

protected function fieldPairExists($value, $rule, $data = [], $field = ''): bool|string
{
    list($fieldPair, $table) = explode(',', $rule);
    list($field1, $field2) = explode('&', $fieldPair);

    if (!is_array($value)) {
        return "{$field}必须为数组";
    }

    $pairs = [];
    foreach ($value as $index => $item) {
        if (!is_array($item)) {
            return "{$field}[{$index}]必须为数组";
        }
        if (!isset($item[$field1])) {
            return "{$field}[{$index}]缺少{$field1}字段";
        }
        if (!isset($item[$field2])) {
            return "{$field}[{$index}]缺少{$field2}字段";
        }

        $val1 = (int)$item[$field1];
        $val2 = (int)$item[$field2];
        if ($val1 <= 0 || $val2 <= 0) {
            return "{$field}[{$index}]的{$field1}或{$field2}必须为正整数";
        }

        $pairs[$index] = [
            $field1 => $val1,
            $field2 => $val2
        ];
    }

    if (empty($pairs)) {
        return true;
    }

    // 构建查询（使用AS指定别名，确保键名统一）
    $concatExpr = "CONCAT({$field1}, '_', {$field2}) AS pair";
    $query = Db::name($table)->where(function($q) use ($pairs, $field1, $field2) {
        foreach ($pairs as $i => $pair) {
            $condition = [
                $field1 => $pair[$field1],
                $field2 => $pair[$field2]
            ];
            $i === 0 ? $q->where($condition) : $q->whereOr($condition);
        }
    });

    // 执行查询并提取值（处理关联数组结构）
    $rawResults = $query->column($concatExpr);

    // 从关联数组中提取字符串值（关键修复）
    $validPairs = [];
    foreach ($rawResults as $item) {
        // 确保获取到正确的字符串值（兼容不同数据库驱动的返回格式）
        $value = is_array($item) ? ($item['pair'] ?? '') : $item;
        if (is_string($value) && $value !== '') {
            $validPairs[] = $value;
        }
    }

    // 安全翻转数组
    $validPairsMap = array_flip($validPairs);
    // 检查每个组合
    foreach ($pairs as $index => $pair) {
        $key = "{$pair[$field1]}_{$pair[$field2]}";
        if (!isset($validPairsMap[$key])) {
            return "{$field}[{$index}]中存在无效的{$field1}和{$field2}组合（{$key}）";
        }
    }

    return true;
}


}
    