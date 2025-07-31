<?php

namespace app\request\admin\config;

use app\common\BaseRequest;
use think\facade\Request;

class UpdateConfig extends BaseRequest
{
    public function rules(): array
    {
        $configId =Request::route('system_config_id')??0; // 获取当前配置ID，用于唯一校验排除
        return [
            'config_key' => "require|string|unique:system_config,config_key,{$configId},system_config_id",
            'config_name' => 'require|string',
            'system_config_group_id' => 'require|integer|exists:system_config_group,system_config_group_id', // 所属分组
            'config_type' => 'require|integer',
            'options' => 'array',
            'sort' => 'integer',
            'is_enabled' => 'require|integer|in:2,1',
        ];
    }

    public function message(): array
    {
        return [
        ];
    }
}
