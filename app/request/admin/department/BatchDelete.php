<?php

namespace app\request\admin\department;

use app\common\BaseRequest;

class BatchDelete extends BaseRequest
{
    public function rules(): array
    {
        return [
            'ids' => 'require|array|min:1',
            'ids.*' => 'integer|gt:0',
        ];
    }

    public function message(): array
    {
        return [
            'ids.require' => '请选择要删除的部门',
            'ids.array' => '部门ID必须是数组格式',
            'ids.min' => '至少选择一个部门',
            'ids.*.integer' => '部门ID必须是整数',
            'ids.*.gt' => '部门ID必须大于0',
        ];
    }
}
