<?php

namespace app\request\admin\region;

use app\common\BaseRequest;

class Create extends BaseRequest
{
    public function rules()
    {
        return [
            'name' => 'require|max:100|unique:region',
            'parent_id' => 'integer|default:0',
            'type' => 'require|in:province,city,district,street,town',
            'code' => 'max:20',
            'snum' => 'integer|default:0',
            'level' => 'integer|default:1'
        ];
    }

    public function message(): array
    {
        return [
            'name.require' => '地区名称不能为空',
            'name.max' => '地区名称不能超过100个字符',
            'name.unique' => '地区名称已存在',
            'parent_id.integer' => '父级ID必须为整数',
            'type.require' => '地区类型不能为空',
            'type.in' => '地区类型不正确',
            'code.max' => '地区编码不能超过20个字符',
            'snum.integer' => '排序号必须为整数'
        ];
    }
}