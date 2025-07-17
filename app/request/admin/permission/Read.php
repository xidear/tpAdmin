<?php

namespace app\request\admin\permission;

use app\common\BaseRequest;

class Read extends BaseRequest
{

    public function __construct()
    {
        parent::__construct();
    }

    public function rules(): array
    {
        if (request()->isGet()) {
            return [ 'id'=>"require"];
        }
        return [        ];
    }

    public function message(): array
    {
        return [];
    }

}