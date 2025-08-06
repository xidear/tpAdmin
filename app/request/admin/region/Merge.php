<?php

namespace app\request\admin\region;

use app\common\BaseRequest;

class Merge extends BaseRequest
{
    public function rules()
    {
        return [
            'target_region_id' => 'require|integer|exists:region,region_id',
            'source_region_ids' => 'require|array',
            'source_region_ids.*' => 'integer|exists:region,region_id'
        ];
    }

    public function message(): array
    {
        return [
            'target_region_id.require' => '目标地区ID不能为空',
            'target_region_id.integer' => '目标地区ID必须为整数',
            'target_region_id.exists' => '目标地区不存在',
            'source_region_ids.require' => '被合并地区ID数组不能为空',
            'source_region_ids.array' => '被合并地区ID必须为数组',
            'source_region_ids.*.integer' => '被合并地区ID必须为整数',
            'source_region_ids.*.exists' => '被合并地区不存在'
        ];
    }
}