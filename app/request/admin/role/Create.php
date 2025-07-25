<?php

namespace app\request\admin\role;

use app\common\BaseRequest;

class Create extends BaseRequest
{
    public function __construct()
    {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'name' => 'require|max:50|unique:role',
            'role_menus' => 'require|array',
            'role_permissions' => 'require|array',
        ];
    }

    public function message(): array
    {
        return [];
    }
}