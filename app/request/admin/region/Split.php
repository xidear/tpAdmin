<?php

namespace app\request\admin\region;

use app\common\BaseRequest;

class Split extends BaseRequest
{
    public function rules()
    {
        return [
            'parent_region_id' => 'require|integer|exists:region,region_id',
            'new_regions' => 'require|array',
            'new_regions.*.name' => 'require|max:100|unique:region',
            'new_regions.*.type' => 'require|in:province,city,district,street,town',
            'new_regions.*.code' => 'max:20',
            'new_regions.*.snum' => 'integer|default:0'
        ];
    }

    public function message(): array
    {
        return [
            'parent_region_id.require' => '父地区ID不能为空',
            'parent_region_id.integer' => '父地区ID必须为整数',
            'parent_region_id.exists' => '父地区不存在',
            'new_regions.require' => '新地区数组不能为空',
            'new_regions.array' => '新地区必须为数组',
            'new_regions.*.name.require' => '新地区名称不能为空',
            'new_regions.*.name.max' => '新地区名称不能超过100个字符',
            'new_regions.*.name.unique' => '新地区名称已存在',
            'new_regions.*.type.require' => '新地区类型不能为空',
            'new_regions.*.type.in' => '新地区类型不正确',
            'new_regions.*.code.max' => '新地区编码不能超过20个字符',
            'new_regions.*.snum.integer' => '新地区排序号必须为整数'
        ];
    }
}