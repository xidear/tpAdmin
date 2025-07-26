<?php

namespace app\request\admin\role;

use app\request\admin\role\Create;
use think\Exception;

class Edit extends Create
{
    public function __construct()
    {
        parent::__construct();
    }

    public function rules(): array
    {
        $roleId = request()->param('role_id', 0);
        if (empty($roleId)){
            throw  new Exception("没找到role_id".$roleId);
        }

        $rules = parent::rules();

        $rules['name'] = "require|max:50|unique:role,name,{$roleId},role_id";
        $rules['role_id'] = 'require|integer|exists:role,role_id';

        // 字段名,目标值
        $rules['role_menus'] = "require|array|validFieldValue:role_id,{$roleId}|fieldExists:menu_id,menu,menu_id|checkDuplicates:menu_id";
        $rules['role_permissions'] = "require|array|validFieldValue:role_id,{$roleId}|fieldPairExists:menu_id&permission_id,menu_permission_dependency|checkDuplicates:menu_id&permission_id";

        return $rules;
    }

    public function message(): array
    {
        $message = parent::message();
        $message['role_id.require'] = '角色ID不能为空';
        $message['role_id.integer'] = '角色ID必须为整数';
        $message['role_id.exists'] = '角色不存在';

        return $message;
    }
}