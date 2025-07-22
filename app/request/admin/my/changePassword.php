<?php

namespace app\request\admin\my;

use app\common\BaseRequest;

class changePassword extends BaseRequest
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
            'old_password'=>"require",
            'password'=>"require|password",
            'password_confirm'=>"require",
        ];
    }

    public function message(): array
    {
        return [];
    }

}