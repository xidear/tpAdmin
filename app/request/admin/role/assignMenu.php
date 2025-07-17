<?php

namespace app\request\admin\role;

use app\common\BaseRequest;

class assignMenu extends BaseRequest
{

    //这里返回值不对
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
            'confirm_password'=>"require|confirm:password",
        ];
    }

    public function message(): array
    {
        return [];
    }

}