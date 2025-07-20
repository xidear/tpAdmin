<?php

namespace app\request\admin\menu;

use app\common\BaseRequest;
use think\facade\Log;
use think\facade\Request;

class Update extends Create
{
    public function rules()
    {
        $rules = parent::rules();
        // 获取当前菜单ID（用于name的唯一性验证）
        $menuId = Request::param('menu_id');

        // 修改name的唯一性验证（排除当前记录）
        if ($menuId) {
            $rules['name'] = str_replace(
                'unique:menu',
                "unique:menu,name,{$menuId},menu_id",
                $rules['name']
            );
        }

        // 安全移除require并清理空分隔符
        foreach ($rules as $field => &$rule) {
            // 跳过menu_id（必须保留require）
            if ($field === 'menu_id') {
                continue;
            }

            // 处理字符串规则
            if (is_string($rule)) {
                // 移除require并清理空分隔符
                $rule = preg_replace('/\brequire\b\|\?/', '', $rule); // 移除require
                $rule = preg_replace('/^\||\|$/', '', $rule); // 清理开头/结尾的|
                $rule = preg_replace('/\|+/', '|', $rule); // 合并连续的||
            }
            // 处理数组规则（如果有）
            elseif (is_array($rule)) {
                $rule = array_filter($rule, function ($item) {
                    return !is_string($item) || !str_contains($item, 'require');
                });
            }
        }

        return $rules;
    }
    public function message(): array
    {
        return array_merge(parent::message(), [
            'menu_id.require' => '菜单ID不能为空',
            'menu_id.exists' => '菜单ID不存在',
            'name.unique' => '菜单标识已被其他菜单使用',
        ]);
    }
}