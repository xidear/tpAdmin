<?php

namespace app\request\admin\login;

use app\common\BaseRequest;

class doLogin extends BaseRequest
{

    public function __construct()
    {
        parent::__construct();
    }

    public function rules(): array
    {
        if (request()->isGet()) {

            return [];
        }
        return [
            'username'=>"require",
            'password'=>"require",
        ];
    }

    public function message(): array
    {
        return [];
    }

}