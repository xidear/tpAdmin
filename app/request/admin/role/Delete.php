<?php

namespace app\request\admin\role;

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
        ];
    }

    public function message(): array
    {
        return [];
    }

}