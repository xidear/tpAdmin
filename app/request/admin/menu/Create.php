<?php

namespace app\request\admin\menu;

use app\common\BaseRequest;

class Create extends BaseRequest
{
    public function rules()
    {
        return [
            'title' => 'require|chsAlphaNum|max:50',
            'icon' => 'require|alphaNum|max:100',
            'name' => 'require|alphaNum|max:100|unique:menu',
            'parent_id' => 'integer|min:0|exists:menu,menu_id',
            'order_num' => 'require|integer|min:0|max:9999',
            'visible' => 'in:1,2',
            'is_link' => 'in:1,2',
            'is_full' => 'in:1,2',
            'is_affix' => 'in:1,2',
            'is_keep_alive' => 'in:1,2',
            'component' => 'requireIf:is_link,2|max:255',
            'link_url' => 'requireIf:is_link,1|url|max:255',
            'redirect' => 'max:255',
            'dependencies' => 'array|checkRequiredDependency',
//            'dependencies.*.permission_id' => 'integer|min:1|exists:permission,permission_id',
//            'dependencies.*.type' => 'in:REQUIRED,OPTIONAL',
//            'dependencies.*.permission_type' => 'in:button,data,filter',
        ];
    }

    public function message(): array
    {
        return [
            'title.require' => '菜单标题不能为空',
            'title.chsAlphaNum' => '菜单标题只能包含中文、字母和数字',
            'title.max' => '菜单标题最多50个字符',

            'icon.require' => '菜单图标不能为空',
            'icon.alphaNum' => '图标名称只能包含字母和数字',
            'icon.max' => '图标名称最多100个字符',

            'name.require' => '菜单标识不能为空',
            'name.alphaNum' => '菜单标识只能包含字母和数字',
            'name.max' => '菜单标识最多100个字符',
            'name.unique' => '菜单标识已存在',

            'parent_id.integer' => '父级菜单ID必须为整数',
            'parent_id.min' => '父级菜单ID不能为负数',
            'parent_id.exists' => '父级菜单不存在',

            'order_num.require' => '排序号不能为空',
            'order_num.integer' => '排序号必须为整数',
            'order_num.min' => '排序号不能小于0',
            'order_num.max' => '排序号不能大于9999',

            'visible.in' => '显示状态只能是1(显示)或2(隐藏)',
            'is_link.in' => '是否为外部链接只能是1(是)或2(否)',
            'is_full.in' => '是否全屏只能是1(是)或2(否)',
            'is_affix.in' => '是否固定只能是1(是)或2(否)',
            'is_keep_alive.in' => '是否缓存只能是1(是)或2(否)',

            'component.requireIf' => '内部路由必须填写文件路径',
            'component.max' => '文件路径最多255个字符',

            'link_url.requireIf' => '外部链接必须填写URL地址',
            'link_url.url' => '外部链接格式不正确',
            'link_url.max' => '外部链接最多255个字符',

            'redirect.max' => '重定向路径最多255个字符',

            'dependencies.require' => '必须添加至少一个权限依赖',
            'dependencies.array' => '权限依赖必须是数组',
            'dependencies.min' => '至少需要一个权限依赖',

            'dependencies.*.permission_id.require' => '权限ID不能为空',
            'dependencies.*.permission_id.integer' => '权限ID必须为整数',
            'dependencies.*.permission_id.min' => '权限ID必须大于0',
            'dependencies.*.permission_id.exists' => '权限ID不存在',

            'dependencies.*.type.require' => '依赖类型不能为空',
            'dependencies.*.type.in' => '依赖类型只能是REQUIRED或OPTIONAL',

            'dependencies.*.permission_type.require' => '权限类型不能为空',
            'dependencies.*.permission_type.in' => '权限类型只能是按钮、列表或筛选',
        ];
    }
}