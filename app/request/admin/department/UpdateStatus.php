<?php

namespace app\request\admin\department;

use app\common\BaseRequest;
use app\common\enum\Status;

class UpdateStatus extends BaseRequest
{
    public function rules(): array
    {
        return [
            'status' => 'require|in:' . Status::getKeyListString(),
        ];
    }

    public function message(): array
    {
        return [
            'status.require' => '状态值不能为空',
            'status.in' => '状态值无效',
        ];
    }
}
