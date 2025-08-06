<?php

namespace app\request\admin\region;

use app\common\BaseRequest;

class Delete extends BaseRequest
{
    public function rules()
    {
        return [
            'region_id' => 'require|integer|exists:region,region_id'
        ];
    }

    public function message(): array
    {
        return [
            'region_id.require' => '地区ID不能为空',
            'region_id.integer' => '地区ID必须为整数',
            'region_id.exists' => '地区不存在'
        ];
    }
}