<?php

namespace app\request\admin\config_group;

use app\common\BaseRequest;

class Update extends Create
{
    public function rules(): array
    {
        // 从路由参数获取group_id
        $groupId = request()->route('group_id') ?? 0;
        
        return [
            'group_name' => 'require|max:255|unique:system_config_group,group_name,' . $groupId . ',system_config_group_id',
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