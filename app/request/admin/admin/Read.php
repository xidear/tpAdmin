<?php

namespace app\request\admin\admin;

use app\common\BaseRequest;

class Read extends BaseRequest
{

    public function __construct()
    {
        parent::__construct();
    }

    public function rules(): array
    {
        return [        ];
    }

    public function message(): array
    {
        return [];
    }

}