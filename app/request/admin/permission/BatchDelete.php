<?php

namespace app\request\admin\permission;

use app\common\BaseRequest;

class BatchDelete extends BaseRequest
{

    public function __construct()
    {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'ids' => "require|array",
        ];
    }

    public function message(): array
    {
        return [];
    }

}