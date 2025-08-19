<?php

namespace app\request\admin\department;

use app\common\BaseRequest;

class UpdatePosition extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => 'require|max:100',
            'code' => 'max:50',
            'sort' => 'integer|egt:0',
            'status' => 'in:0,1',
            'description' => 'max:500',
        ];
    }

    public function message(): array
    {
        return [
            'name.require' => '职位名称不能为空',
            'name.max' => '职位名称不能超过100个字符',
            'code.max' => '职位编码不能超过50个字符',
            'sort.integer' => '排序必须是整数',
            'sort.egt' => '排序不能小于0',
            'status.in' => '状态值无效',
            'description.max' => '描述不能超过500个字符',
        ];
    }
}
