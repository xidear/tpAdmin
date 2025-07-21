<?php

namespace app\request\admin\admin;

use app\request\admin\admin\Create;

class Edit extends Create
{
    public function __construct()
    {
        parent::__construct();
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['admin_id'] = 'require|integer|exists:admin,admin_id';
        return $rules;
    }

}