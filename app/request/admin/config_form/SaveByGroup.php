<?php

namespace app\request\admin\config_form;

use app\common\BaseRequest;

class SaveByGroup extends BaseRequest
{


    public function __construct()
    {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'group_id' => 'require|integer',
            'fields' => 'require|array'
        ];
    }

    public function message(): array
    {
        return  [
            'group_id.require' => '配置分组不能为空',
            'fields.require' => '配置数据不能为空',
            'fields.array' => '配置数据必须为数组'
        ];
    }



}