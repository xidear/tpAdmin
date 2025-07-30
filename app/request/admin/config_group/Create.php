<?php

namespace app\request\admin\config_group;

use app\common\BaseRequest;

class Create extends BaseRequest
{
    public function rules(): array
    {
        return [
            'group_name' => 'require|max:255|unique:system_config_group',
            'sort' => 'integer'
        ];
    }

    public function message(): array
    {
        return [
            'group_name.require' => '分组名称不能为空',
            'group_name.max' => '分组名称长度不能超过255个字符',
            'group_name.unique' => '分组名称已存在',
            'sort.integer' => '排序值必须为整数'
        ];
    }
}