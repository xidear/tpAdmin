<?php

namespace app\request\admin\role;

use app\common\BaseRequest;
use app\common\ExtendValidate;

class Create extends BaseRequest
{
    protected $validate = ExtendValidate::class;

    public function __construct()
    {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'name' => 'require|max:50|unique:role',
            // 字段名,表名,数据库字段名（第三个参数可选）
            'role_menus' => 'require|array|noField:role_id|fieldExists:menu_id,menu,menu_id|checkDuplicates:menu_id',
            // 字段1&字段2,表名
            'role_permissions' => 'require|array|noField:role_id|fieldPairExists:menu_id&permission_id,menu_permission_dependency|checkDuplicates:menu_id&permission_id',
        ];
    }

    public function message(): array
    {
        return [
            'name.require' => '角色名称不能为空',
            'name.max' => '角色名称不能超过50个字符',
            'name.unique' => '角色名称已存在',
            'role_menus.require' => '角色菜单不能为空',
            'role_menus.array' => '角色菜单必须为数组',
            'role_permissions.require' => '角色权限不能为空',
            'role_permissions.array' => '角色权限必须为数组',
        ];
    }
}