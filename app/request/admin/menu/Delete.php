<?php

namespace app\request\admin\login;

use app\common\BaseRequest;

class Delete extends BaseRequest
{

    public function __construct()
    {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'id'=>"require",
        ];
    }

    public function message(): array
    {
        return [];
    }

}