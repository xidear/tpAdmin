<?php

namespace app\request\admin\region;

use app\common\BaseRequest;
use think\facade\Request;

class Update extends Create
{
    public function rules()
    {
        $rules = parent::rules();
        $regionId = Request::param('region_id');

        // 修改name的唯一性验证（排除当前记录）
        if ($regionId) {
            $rules['name'] = str_replace(
                'unique:region',
                "unique:region,name,{$regionId},region_id",
                $rules['name']
            );
        }

        // 移除部分字段的必填验证
        $rules['name'] = str_replace('require|', '', $rules['name']);
        $rules['type'] = str_replace('require|', '', $rules['type']);

        // 添加region_id验证
        $rules['region_id'] = 'require|integer|exists:region,region_id';

        return $rules;
    }

    public function message(): array
    {
        return array_merge(parent::message(), [
            'region_id.require' => '地区ID不能为空',
            'region_id.integer' => '地区ID必须为整数',
            'region_id.exists' => '地区不存在',
            'name.unique' => '地区名称已被使用'
        ]);
    }
}