<?php

namespace app\request\admin\my;

use app\common\BaseRequest;

class updateProfile extends BaseRequest
{

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
            'username' => 'require|max:20|unique:admin,username,' . request()->adminId . ',admin_id',
            'nick_name' => 'require|max:20',
            'avatar' => 'max:255',
        ];
    }

    public function message(): array
    {
        return [
            'username.require' => '用户名不能为空',
            'username.max' => '用户名长度不能超过20个字符',
            'username.unique' => '用户名已存在',
            'nick_name.require' => '昵称不能为空',
            'nick_name.max' => '昵称长度不能超过20个字符',
            'avatar.max' => '头像URL长度不能超过255个字符',
        ];
    }

}
