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

            // 根据文件类型进行验证
            if (!$this->validateFileType($file, $fileType)) {
                return $this->error($this->getFileTypeErrorMessage($fileType));
            }

            // 存储配置
            $storageType = $this->request->param('storage_type', FileModel::STORAGE_LOCAL);
            $disk = $storageType === FileModel::STORAGE_LOCAL ? 'public' : $storageType;

            // 上传文件
            $path = Filesystem::disk($disk)->putFile('uploads', $file);

            if (!$path) {
                return $this->error('文件上传失败');
            }

            // 获取上传者类型和ID
            $uploaderType = $this->request->param('uploader_type', FileModel::UPLOADER_ADMIN);
            $uploaderId = 0;

            // 根据上传者类型获取正确的ID
            switch ($uploaderType) {
                case FileModel::UPLOADER_USER:
                    return $this->error('无法以用户身份上传');
                case FileModel::UPLOADER_ADMIN:
                    // 从管理员会话获取ID（假设管理员ID存储在admin_id中）
                    $uploaderId = request()->adminId;
                    if (!$uploaderId) {
                        return $this->error('管理员未登录，无法以管理员身份上传');
                    }
                    break;
                case FileModel::UPLOADER_SYSTEM:
                    // 系统上传，使用固定ID或配置值
                    $uploaderId = config('system.uploader_id', (new \app\model\Admin())->getSuperAdminId());
                    break;
            }

            // 构建文件信息
            $fileInfo = [
                'origin_name' => $file->getOriginalName(),
                'file_name' => basename($path), // 使用存储后的文件名
                'size' => $file->getSize(),
                'mime_type' => $file->getOriginalMime(),
                'storage_type' => $storageType,
                'storage_path' => $path,
                'url' => $this->generateFileUrl($storageType, $path), // 生成完整URL
                'access_domain' => $this->getAccessDomain($storageType), // 存储访问域名
                'storage_permission' => $this->request->param('permission', FileModel::PERMISSION_PUBLIC),
                'uploader_type' => $uploaderType,
                'uploader_id' => $uploaderId
            ];

            // 保存文件信息到数据库
            $fileModel = FileModel::create($fileInfo);

            return $this->success(['url'=>$fileModel->url], '文件上传成功');

        } catch (\Exception $e) {
            return $this->error('文件上传失败: ' . $e->getMessage());
        }
    }

    /**
     * 根据文件类型验证文件
     * @param \think\File $file
     * @param string $fileType
     * @return bool
     */
    private function validateFileType(\think\File $file, string $fileType): bool
    {
        // 允许的文件类型配置
        $allowedTypes = [
            'image' => ['jpg', 'jpeg', 'png', 'gif'],
            'video' => ['mp4', 'avi', 'mov', 'mkv'],
            'file' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar']
        ];

        // 如果是all类型或未知类型，不进行额外验证
        if ($fileType === 'all' || !isset($allowedTypes[$fileType])) {
            return true;
        }

        // 获取文件扩展名
        $extension = strtolower(pathinfo($file->getOriginalName(), PATHINFO_EXTENSION));

        // 验证文件类型
        return in_array($extension, $allowedTypes[$fileType]);
    }

    /**
     * 获取文件类型错误信息
     * @param string $fileType
     * @return string
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

    /**
     * 生成文件访问URL
     * @param string $storageType 存储类型
     * @param string $path 存储路径
     * @return string 文件URL
     */
    private function generateFileUrl(string $storageType, string $path): string
    {
        $domain = $this->getAccessDomain($storageType);

        // 本地存储使用本地域名拼接路径
        if ($storageType === FileModel::STORAGE_LOCAL) {
            return $domain . '/storage/' . $path;
        }

        // 其他存储类型（如OSS、COS）可能有自己的URL生成规则
        return match ($storageType) {
            FileModel::STORAGE_QCLOUD_COS, FileModel::STORAGE_AWS_S3, FileModel::STORAGE_ALIYUN_OSS => "https://{$domain}/{$path}",
            default => $path,
        };
    }

    /**
     * 获取存储类型对应的访问域名
     * @param string $storageType 存储类型
     * @return string 访问域名
     */
    private function getAccessDomain(string $storageType): string
    {
        // 从配置文件获取域名配置
        $domainConfig = config('filesystems.access_domains', []);

        // 根据存储类型获取对应的域名
        if (isset($domainConfig[$storageType])) {
            return $domainConfig[$storageType];
        }

        // 本地存储默认使用当前请求域名
        if ($storageType === FileModel::STORAGE_LOCAL) {
            return request()->domain();
        }

        // 其他存储类型返回空或默认域名
        return '';
    }
}