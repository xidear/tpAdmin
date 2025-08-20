<?php
namespace app\request\admin\file;

use app\common\BaseRequest;
use app\common\enum\file\FileStorageType;
use app\common\enum\file\FileStoragePermission;
use app\common\enum\file\FileUploaderType;
use app\model\SystemConfig;
use think\Request;

class Upload extends BaseRequest
{
    public function rules(): array
    {
        // 动态获取文件大小限制，优先使用数据库配置，兜底使用 .env 配置
        $maxFileSize = $this->getMaxFileSize();
        
        return [
            'file' => [
                'require',
                'file',
                'fileSize:' . $maxFileSize,
                'fileExt:' . $this->getAllowedExtensions()
            ],
            'storage_type' => 'require|in:' . implode(',', [
                    FileStorageType::Local->value,
                    FileStorageType::AliyunOss->value,
                    FileStorageType::QcloudCos->value,
                    FileStorageType::AwsS3->value
                ]),
            'storage_permission' => 'in:' . implode(',', [
                    FileStoragePermission::Public->value,
                    FileStoragePermission::Private->value
                ]),
            'uploader_type' => 'require|in:' . implode(',', [
                    FileUploaderType::User->value,
                    FileUploaderType::System->value,
                    FileUploaderType::Admin->value
                ]),
            'uploader_id' => 'requireIf:uploader_type,user|integer'
        ];
    }

    public function message(): array
    {
        $maxFileSizeMB = $this->getMaxFileSize() / 1024 / 1024;
        
        return [
            'file.require' => '请选择要上传的文件',
            'file.file' => '上传的内容不是有效的文件',
            'file.fileSize' => "文件大小不能超过{$maxFileSizeMB}MB",
            'file.fileExt' => '不支持的文件类型，允许的类型：' . $this->getAllowedExtensions(),
            'storage_type.require' => '请选择存储类型',
            'storage_type.in' => '无效的存储类型',
            'storage_permission.in' => '无效的存储权限',
            'uploader_type.require' => '请指定上传者类型',
            'uploader_type.in' => '无效的上传者类型',
            'uploader_id.requireIf' => '用户上传时必须指定上传者ID',
            'uploader_id.integer' => '上传者ID必须为整数'
        ];
    }

    /**
     * 获取最大文件大小限制
     * 优先级：数据库配置 > .env 配置 > 默认值
     * @return int
     */
    protected function getMaxFileSize(): int
    {
        try {
            // 1. 优先从数据库配置获取
            $commonConfig = SystemConfig::getCacheValue('upload_common_config', '{}');
            $config = json_decode($commonConfig, true);
            
            if (isset($config['max_file_size']) && is_numeric($config['max_file_size'])) {
                return (int)$config['max_file_size'];
            }
        } catch (\Exception $e) {
            // 忽略错误，继续使用兜底配置
        }

        // 2. 兜底使用 .env 配置
        $envMaxSize = env('MAX_FILE_SIZE');
        if ($envMaxSize && is_numeric($envMaxSize)) {
            return (int)$envMaxSize;
        }

        // 3. 最终兜底值：10MB
        return 10 * 1024 * 1024;
    }

    /**
     * 获取允许的文件扩展名
     * 优先级：数据库配置 > .env 配置 > 默认值
     * @return string
     */
    protected function getAllowedExtensions(): string
    {
        try {
            // 1. 优先从数据库配置获取
            $commonConfig = SystemConfig::getCacheValue('upload_common_config', '{}');
            $config = json_decode($commonConfig, true);
            
            if (isset($config['allowed_extensions']) && !empty($config['allowed_extensions'])) {
                return $config['allowed_extensions'];
            }
        } catch (\Exception $e) {
            // 忽略错误，继续使用兜底配置
        }

        // 2. 兜底使用 .env 配置（如果有的话）
        $envExtensions = env('ALLOWED_FILE_EXTENSIONS');
        if ($envExtensions) {
            return $envExtensions;
        }

        // 3. 最终兜底值
        return 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,mp4,zip,rar';
    }
}