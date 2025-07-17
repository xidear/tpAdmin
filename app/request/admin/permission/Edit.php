<?php

namespace app\request\admin\permission;

class Edit extends Create
{
    public function __construct()
    {
        parent::__construct();
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['permission_id'] = 'require|integer';
        return $rules;
    }

}