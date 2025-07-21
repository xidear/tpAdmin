<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\model\File as FileModel;
use app\request\admin\file\Upload;
use think\facade\Filesystem;
use think\Response;

class File extends BaseController
{
    /**
     * 获取文件列表
     * @return Response
     */
    public function index(): Response
    {
        $list = (new FileModel())->fetchPaginated();
        return $this->success($list);
    }

    /**
     * 获取文件详情
     * @param string $file_id
     * @return Response
     */
    public function read(string $file_id): Response
    {
        $file = (new \app\model\File)->fetchOne($file_id);
        if ($file->isEmpty()) {
            return $this->error('文件不存在');
        }
        return $this->success($file);
    }

    /**
     * 上传文件
     * @param Upload $validate
     * @return Response
     */
    public function upload(Upload $validate): Response
    {
        // 获取上传的文件
        $file = $this->request->file('file');
        if (!$file) {
            return $this->error('请选择上传的文件1');
        }

        try {
            // 获取文件类型参数
            $fileType = $this->request->param('file_type', 'all');
            // 验证文件类型
            if (!$this->validateFileType($file, $fileType)) {
                return $this->error($this->getFileTypeErrorMessage($fileType));
            }

            // 1. 存储配置：读取磁盘配置，避免硬编码磁盘名称
            $storageType = $this->request->param('storage_type', FileModel::STORAGE_LOCAL);
            // 根据存储类型映射到对应的磁盘（结合现有配置中的磁盘）
            $disk = $storageType === FileModel::STORAGE_LOCAL ? 'public' : $storageType;
            // 从配置中获取当前磁盘的信息（用于后续路径处理）
            $diskConfig = config("filesystem.disks.{$disk}");

            // 2. 上传目录：使用磁盘默认根目录下的"uploads"（不硬编码，可根据磁盘动态调整）
            // 注：若需自定义子目录，可从配置或参数获取，这里保持与原逻辑一致用"uploads"
            $uploadSubDir = 'uploads';

            // 3. 上传文件（路径基于磁盘配置的root，无需硬编码绝对路径）
            $path = Filesystem::disk($disk)->putFile($uploadSubDir, $file);
            if (!$path) {
                return $this->error('文件上传失败');
            }

            // 4. 获取上传者信息
            $uploaderType = $this->request->param('uploader_type', FileModel::UPLOADER_ADMIN);
            $uploaderId = 0;
            switch ($uploaderType) {
                case FileModel::UPLOADER_USER:
                    return $this->error('无法以用户身份上传');
                case FileModel::UPLOADER_ADMIN:
                    $uploaderId = request()->adminId;
                    if (!$uploaderId) {
                        return $this->error('管理员未登录，无法以管理员身份上传');
                    }
                    break;
                case FileModel::UPLOADER_SYSTEM:
                    $uploaderId = config('system.uploader_id', (new \app\model\Admin())->getSuperAdminId());
                    break;
            }

            // 5. 构建文件信息（URL基于磁盘的url配置，避免硬编码/storage）
            $fileInfo = [
                'origin_name' => $file->getOriginalName(),
                'file_name' => basename($path), // 存储后文件名
                'size' => $file->getSize(),
                'mime_type' => $file->getOriginalMime(),
                'storage_type' => $storageType,
                'storage_path' => $path, // 相对磁盘root的路径（与配置一致）
                // URL = 域名 + 磁盘的url配置 + 相对路径（例如：domain + /storage + /uploads/xxx.jpg）
                'url' => $this->generateFileUrl($disk, $diskConfig, $path),
                'access_domain' => $this->getAccessDomain($storageType),
                'storage_permission' => $this->request->param('permission', FileModel::PERMISSION_PUBLIC),
                'uploader_type' => $uploaderType,
                'uploader_id' => $uploaderId
            ];

            // 保存到数据库
            $fileModel = FileModel::create($fileInfo);
            return $this->success(['url' => $fileModel->url], '文件上传成功');

        } catch (\Exception $e) {
            return $this->error('文件上传失败: ' . $e->getMessage());
        }
    }

    /**
     * 生成文件访问URL（基于磁盘配置的url，避免硬编码）
     * @param string $disk 磁盘名称（local/public/aliyun_oss等）
     * @param array $diskConfig 磁盘配置（包含url等信息）
     * @param string $path 文件相对路径
     * @return string
     */
    private function generateFileUrl(string $disk, array $diskConfig, string $path): string
    {
        $domain = $this->getAccessDomain(FileModel::STORAGE_LOCAL); // 本地域名

        // 本地存储（public磁盘）：使用配置中的url（即/storage）
        if ($disk === 'public') {
            $urlPrefix = $diskConfig['url'] ?? '/storage'; // 从配置取url前缀
            return rtrim($domain, '/') . '/' . ltrim($urlPrefix, '/') . '/' . ltrim($path, '/');
        }

        // 其他存储（OSS/COS等）：保持原逻辑，基于domain和path
        return match ($disk) {
            'aliyun_oss', 'qcloud_cos', 'aws_s3' => "https://{$domain}/{$path}",
            default => $path,
        };
    }

    /**
     * 获取访问域名（保持原逻辑）
     */
    private function getAccessDomain(string $storageType): string
    {
        $domainConfig = config('filesystems.access_domains', []);
        if (isset($domainConfig[$storageType])) {
            return $domainConfig[$storageType];
        }
        // 本地存储默认使用当前请求域名
        if ($storageType === FileModel::STORAGE_LOCAL) {
            return request()->domain();
        }
        return '';
    }

    /**
     * 文件类型验证（保持原逻辑）
     */
    private function validateFileType(\think\File $file, string $fileType): bool
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
     * 文件类型错误信息（保持原逻辑）
     */
    private function getFileTypeErrorMessage(string $fileType): string
    {
        $typeMessages = [
            'image' => '请上传有效的图片文件（支持jpg、jpeg、png、gif格式）',
            'video' => '请上传有效的视频文件（支持mp4、avi、mov、mkv格式）',
            'file' => '请上传有效的文档文件（支持pdf、doc、docx、xls、xlsx、zip、rar格式）'
        ];
        return $typeMessages[$fileType] ?? '不支持的文件类型';
    }
}