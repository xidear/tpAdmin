<?php

namespace app\request\admin\permission;

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
            'node' => 'require|regex:/^[a-z0-9]+\/[a-z0-9]+$/i|unique:permission',
            'name' => 'require|max:20',
            'method' => 'require|in:get,post,put,delete,patch',
            'is_public' => 'require|in:1,2'
        ];
    }

    public function message(): array
    {
        return [
            'node.require' => '权限节点不能为空',
            'node.regex' => '权限节点格式不正确，应为：模块/操作',
            'node.unique' => '权限节点已存在',
            'name.require' => '权限名称不能为空',
            'name.max' => '权限名称长度不能超过20个字符',
            'method.require' => '请求方法不能为空',
            'method.in' => '请求方法必须是get,post,put,delete,patch之一',
            'is_public.require' => '是否公开不能为空',
            'is_public.in' => '是否公开的值只能是1或2'
        ];
    }
}