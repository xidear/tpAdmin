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

        // 上传目录
        $uploadSubDir = $options['upload_dir'] ?? 'uploads';

        // 上传文件
        $path = Filesystem::disk($disk)->putFile($uploadSubDir, $file);
        if (!$path) {
            throw new \Exception('文件上传失败');
        }

        // 获取上传者信息
        $uploaderInfo = $this->getUploaderInfo($context);

        // 构建文件信息
        $fileInfo = [
            'origin_name' => $file->getOriginalName(),
            'file_name' => basename($path),
            'size' => $file->getSize(),
            'mime_type' => $file->getOriginalMime(),
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

        $fileType = $options['file_type'] ?? 'all';
        if (!$this->validateFileType($file, $fileType)) {
            throw new \Exception($this->getFileTypeErrorMessage($fileType));
        }
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
        $allowedTypes = [
            'image' => ['jpg', 'jpeg', 'png', 'gif'],
            'video' => ['mp4', 'avi', 'mov', 'mkv'],
            'file' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar']
        ];
        
        if ($fileType === 'all' || !isset($allowedTypes[$fileType])) {
            return true;
        }
        
        $extension = strtolower(pathinfo($file->getOriginalName(), PATHINFO_EXTENSION));
        return in_array($extension, $allowedTypes[$fileType]);
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
