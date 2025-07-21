<?php
namespace app\request\admin\file;

use app\common\BaseRequest;
use think\Request;

class Upload extends BaseRequest
{


    public function rules(): array
    {

        return [
            'storage_type' => 'require|in:' . implode(',', [
                    \app\model\File::STORAGE_LOCAL,
                    \app\model\File::STORAGE_ALIYUN_OSS,
                    \app\model\File::STORAGE_QCLOUD_COS,
                    \app\model\File::STORAGE_AWS_S3
                ]),
            'storage_permission' => 'in:' . implode(',', [
                    \app\model\File::PERMISSION_PUBLIC,
                    \app\model\File::PERMISSION_PRIVATE
                ]),
            'uploader_type' => 'require|in:' . implode(',', [
                    \app\model\File::UPLOADER_USER,
                    \app\model\File::UPLOADER_SYSTEM,
                    \app\model\File::UPLOADER_ADMIN
                ]),
            'uploader_id' => 'requireIf:uploader_type,user|integer'
        ];
    }

    public function message(): array
    {
        return [
            'file.require' => '请选择要上传的文件2',
            'file.file' => '上传的内容不是有效的文件3',
            'file.fileSize' => '文件大小不能超过10MB',
            'file.fileExt' => '不支持的文件类型，允许的类型：jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,mp4,zip,rar',
            'storage_type.require' => '请选择存储类型',
            'storage_type.in' => '无效的存储类型',
            'storage_permission.in' => '无效的存储权限',
            'uploader_type.require' => '请指定上传者类型',
            'uploader_type.in' => '无效的上传者类型',
            'uploader_id.requireIf' => '用户上传时必须指定上传者ID',
            'uploader_id.integer' => '上传者ID必须为整数'
        ];
    }
}