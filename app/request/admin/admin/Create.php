<?php

namespace app\request\admin\admin;

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
            'username' => 'require|max:50|unique:admin',
            'password' => 'requireWithout:admin_id|min:6|confirm',
            'password_confirm' => 'requireWith:password',
            'real_name' => 'require|max:50',
            'nick_name' => 'require|max:50',
            'status' => 'require|in:1,2',
            'avatar' => 'max:255'
        ];
    }

    public function message(): array
    {
        return [
            'username.require' => '用户名不能为空',
            'username.max' => '用户名长度不能超过50个字符',
            'username.unique' => '用户名已存在',
            'password.require_without' => '创建用户时密码不能为空',
            'password.min' => '密码长度不能少于8个字符',
            'password.confirm' => '两次输入的密码不一致',
            'password_confirm.require_with' => '确认密码不能为空',
            'real_name.max' => '真实姓名长度不能超过50个字符',
            'nick_name.max' => '昵称长度不能超过50个字符',
            'status.require' => '用户状态不能为空',
            'status.in' => '用户状态的值只能是1或2',
            'avatar.max' => '头像URL长度不能超过255个字符'
        ];
    }
}