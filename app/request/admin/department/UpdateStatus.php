<?php

namespace app\request\admin\department;

use app\common\BaseRequest;

class UpdateStatus extends BaseRequest
{
    public function rules(): array
    {
        return [
            'status' => 'require|in:0,1',
        ];
    }

    public function message(): array
    {
        return [
            'status.require' => '状态值不能为空',
            'status.in' => '状态值无效，只能是0或1',
        ];
    }
}
