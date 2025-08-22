<?php

namespace app\common\service;

use app\common\enum\file\FileStorageType;
use app\common\enum\file\FileStoragePermission;
use app\common\enum\file\FileUploaderType;
use app\model\File as FileModel;
use app\model\SystemConfig;
use think\facade\Filesystem;
use think\File;

class FileUploadService
{
    /**
     * 上传文件
     * @param File $file 上传的文件
     * @param array $options 上传选项
     * @param string $context 上下文（admin/user/system）
     * @return array
     * @throws \Exception
     */
    public function upload(File $file, array $options = [], string $context = 'admin'): array
    {
        // 验证文件
        $this->validateFile($file, $options);

        // 从配置表获取存储方式
        $storageType = $this->getStorageTypeFromConfig();
        
        // 根据存储类型映射到对应的磁盘
        $disk = $storageType === FileStorageType::Local->value ? 'public' : $storageType;
        
        // 从配置中获取当前磁盘的信息
        $diskConfig = config("filesystem.disks.{$disk}");

        // 获取上传者信息
        $uploaderInfo = $this->getUploaderInfo($context);

        // 上传目录 - 添加日期子目录，确保目录存在
        $uploadSubDir = "uploads/{$uploaderInfo['type']}/{$uploaderInfo['id']}";
        
        // 确保目录存在
        $fullPath = public_path($uploadSubDir);
        if (!is_dir($fullPath)) {
            if (!mkdir($fullPath, 0755, true)) {
                throw new \Exception('无法创建上传目录：' . $fullPath);
            }
        }

        // 上传文件
        $path = Filesystem::disk($disk)->putFile($uploadSubDir, $file);
        if (!$path) {
            throw new \Exception('文件上传失败');
        }


        // 构建文件信息
        $fileInfo = [
            'origin_name' => $file->getOriginalName(),
            'file_name' => basename($path),
            'size' => $file->getSize(),
            'mime_type' => $file->getMime(),
            'storage_type' => $storageType,
            'storage_path' => $path,
            'url' => $this->generateFileUrl($disk, $diskConfig, $path),
            'access_domain' => $this->getAccessDomain($storageType),
            'storage_permission' => $options['permission'] ?? FileStoragePermission::Public->value,
            'uploader_type' => $uploaderInfo['type'],
            'uploader_id' => $uploaderInfo['id']
        ];

        // 保存到数据库
        $fileModel = FileModel::create($fileInfo);
        
        return [
            'file_id' => $fileModel->getKey(),
            'url' => $fileModel->url,
            'file_name' => $fileModel->file_name,
            'size' => $fileModel->size,
            'mime_type' => $fileModel->mime_type
        ];
    }

    /**
     * 验证文件
     * @param File $file
     * @param array $options
     * @throws \Exception
     */
    protected function validateFile(File $file, array $options): void
    {
        if (!$file) {
            throw new \Exception('请选择上传的文件');
        }

        // 验证文件大小
        $this->validateFileSize($file);

        $fileType = $options['file_type'] ?? 'all';
        if (!$this->validateFileType($file, $fileType)) {
            throw new \Exception($this->getFileTypeErrorMessage($fileType));
        }
    }

    /**
     * 验证文件大小
     * @param File $file
     * @throws \Exception
     */
    protected function validateFileSize(File $file): void
    {
        $maxFileSize = $this->getMaxFileSize();
        
        if ($file->getSize() > $maxFileSize) {
            $maxSizeMB = $maxFileSize / 1024 / 1024;
            throw new \Exception("文件大小不能超过{$maxSizeMB}MB");
        }
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
     * @return array
     */
    protected function getAllowedExtensions(): array
    {
        try {
            // 1. 优先从数据库配置获取
            $commonConfig = SystemConfig::getCacheValue('upload_common_config', '{}');
            $config = json_decode($commonConfig, true);
            
            if (!empty($config['allowed_extensions'])) {
                return explode(',', $config['allowed_extensions']);
            }
        } catch (\Exception $e) {
            // 忽略错误，继续使用兜底配置
        }

        // 2. 兜底使用 .env 配置（如果有的话）
        $envExtensions = env('ALLOWED_FILE_EXTENSIONS');
        if ($envExtensions) {
            return explode(',', $envExtensions);
        }

        // 3. 最终兜底值
        return ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'mp4', 'zip', 'rar'];
    }

    /**
     * 从配置表获取存储方式
     * @return string
     */
    protected function getStorageTypeFromConfig(): string
    {
        try {
            // 从系统配置表获取存储方式，如果没有配置则默认使用本地存储
            $storageType = SystemConfig::getCacheValue('upload_storage_type', FileStorageType::Local->value);
            
            // 验证存储类型是否有效
            $validTypes = [
                FileStorageType::Local->value,
                FileStorageType::AliyunOss->value,
                FileStorageType::QcloudCos->value,
                FileStorageType::AwsS3->value
            ];
            
            if (!in_array($storageType, $validTypes)) {
                return FileStorageType::Local->value;
            }
            
            return $storageType;
        } catch (\Exception $e) {
            // 如果获取配置失败，默认使用本地存储
            return FileStorageType::Local->value;
        }
    }

    /**
     * 根据上下文获取上传者信息
     * @param string $context
     * @return array
     * @throws \Exception
     */
    protected function getUploaderInfo(string $context): array
    {
        switch ($context) {
            case 'admin':
                $adminId = request()->adminId ?? 0;
                if (!$adminId) {
                    throw new \Exception('管理员未登录，无法上传文件');
                }
                return [
                    'type' => FileUploaderType::Admin->value,
                    'id' => $adminId
                ];
                
            case 'user':
                $userId = request()->userId ?? 0;
                if (!$userId) {
                    throw new \Exception('用户未登录，无法上传文件');
                }
                return [
                    'type' => FileUploaderType::User->value,
                    'id' => $userId
                ];
                
            case 'system':
                return [
                    'type' => FileUploaderType::System->value,
                    'id' => config('system.uploader_id', (new \app\model\Admin())->getSuperAdminId())
                ];
                
            default:
                throw new \Exception('无效的上传上下文');
        }
    }

    /**
     * 生成文件访问URL
     * @param string $disk
     * @param array $diskConfig
     * @param string $path
     * @return string
     */
    protected function generateFileUrl(string $disk, array $diskConfig, string $path): string
    {
        $domain = $this->getAccessDomain(FileStorageType::Local->value);

        // 本地存储（public磁盘）：使用配置中的url
        if ($disk === 'public') {
            $urlPrefix = $diskConfig['url'] ?? '/storage';
            return rtrim($domain, '/') . '/' . ltrim($urlPrefix, '/') . '/' . ltrim($path, '/');
        }

        // 其他存储（OSS/COS等）
        return match ($disk) {
            'aliyun_oss', 'qcloud_cos', 'aws_s3' => "https://{$domain}/{$path}",
            default => $path,
        };
    }

    /**
     * 获取访问域名
     * @param string $storageType
     * @return string
     */
    protected function getAccessDomain(string $storageType): string
    {
        $domainConfig = config('filesystems.access_domains', []);
        if (isset($domainConfig[$storageType])) {
            return $domainConfig[$storageType];
        }
        
        if ($storageType === FileStorageType::Local->value) {
            // 优先使用系统配置的 site_url
            $siteUrl = SystemConfig::getCacheValue('site_url');
            if ($siteUrl) {
                return rtrim($siteUrl, '/'); // 去掉结尾的 /
            }
            
            // 兜底使用其他方式
            return request()->domain();
        }
        
        return '';
    }

    /**
     * 文件类型验证
     * @param File $file
     * @param string $fileType
     * @return bool
     */
    protected function validateFileType(File $file, string $fileType): bool
    {
        // 使用MIME类型验证，避免临时文件名问题
        $mimeType = $file->getMime();
        
        $allowedMimeTypes = [
            'image' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'],
            'video' => ['video/mp4', 'video/avi', 'video/mov', 'video/mkv', 'video/webm'],
            'file' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/x-rar-compressed']
        ];
        
        if ($fileType === 'all' || !isset($allowedMimeTypes[$fileType])) {
            return true;
        }
        
        // 检查MIME类型是否在允许列表中
        return in_array($mimeType, $allowedMimeTypes[$fileType]);
    }

    /**
     * 文件类型错误信息
     * @param string $fileType
     * @return string
     */
    protected function getFileTypeErrorMessage(string $fileType): string
    {
        $typeMessages = [
            'image' => '请上传有效的图片文件（支持jpg、jpeg、png、gif格式）',
            'video' => '请上传有效的视频文件（支持mp4、avi、mov、mkv格式）',
            'file' => '请上传有效的文档文件（支持pdf、doc、docx、xls、xlsx、zip、rar格式）'
        ];
        
        return $typeMessages[$fileType] ?? '不支持的文件类型';
    }
}
