<?php

namespace app\request\admin\config;

use app\common\BaseRequest;

class CreateConfig extends BaseRequest
{
    public function rules(): array
    {
        return [
            'config_key' => 'require|string|unique:system_config,config_key', // 键名唯一
            'config_name' => 'require|string', // 配置名称
            'system_config_group_id' => 'require|integer|exists:system_config_group,system_config_group_id', // 所属分组
            'config_type' => 'require|integer', // 配置类型（1=文本，2=开关等）
            'options' => 'array', // 选项（下拉框等类型需要）
            'sort' => 'integer', // 排序
            'is_enabled' => 'require|integer|in:2,1', // 是否启用
        ];
    }

    public function message(): array
    {
        return [

        ];
    }
}
