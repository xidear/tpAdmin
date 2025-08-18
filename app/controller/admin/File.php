<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\common\enum\file\FileStorageType;
use app\common\enum\file\FileStoragePermission;
use app\common\enum\file\FileUploaderType;
use app\model\File as FileModel;
use app\model\SystemConfig;
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
        $list = (new FileModel())->fetchData();
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
            return $this->error('请选择上传的文件');
        }

        try {
            // 获取文件类型参数
            $fileType = $this->request->param('file_type', 'all');
            // 验证文件类型
            if (!$this->validateFileType($file, $fileType)) {
                return $this->error($this->getFileTypeErrorMessage($fileType));
            }

            // 从系统配置表获取当前存储方式，而不是从配置文件
            $storageType = $this->getCurrentStorageType();

            // 根据存储类型映射到对应的磁盘
            $disk = $storageType === FileStorageType::Local->value ? 'public' : $storageType;

            // 从配置中获取当前磁盘的信息
            $diskConfig = config("filesystem.disks.{$disk}");

            // 上传目录
            $uploadSubDir = 'uploads';

            // 上传文件
            $path = Filesystem::disk($disk)->putFile($uploadSubDir, $file);
            if (!$path) {
                return $this->error('文件上传失败');
            }

            // 安全获取上传者信息，不依赖请求参数
            $uploaderType = $this->getSecureUploaderType();
            $uploaderId = $this->getSecureUploaderId($uploaderType);

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
                'storage_permission' => $this->getSecureStoragePermission(),
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
        $domain = $this->getAccessDomain(FileStorageType::Local->value); // 本地域名

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
        if ($storageType === FileStorageType::Local->value) {
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

    /**
     * 从系统配置表获取当前存储方式
     * @return string
     */
    private function getCurrentStorageType(): string
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
     * 安全获取上传者类型，不依赖请求参数
     * @return string
     */
    private function getSecureUploaderType(): string
    {
        // 默认使用管理员身份，确保安全性
        return FileUploaderType::Admin->value;
    }

    /**
     * 安全获取上传者ID，基于当前登录状态
     * @param string $uploaderType
     * @return int
     */
    private function getSecureUploaderId(string $uploaderType): int
    {
        switch ($uploaderType) {
            case FileUploaderType::User->value:
                // 用户上传暂时不允许
                throw new \Exception('无法以用户身份上传');
            case FileUploaderType::Admin->value:
                // 从当前请求获取管理员ID，确保已登录
                $uploaderId = request()->adminId;
                if (!$uploaderId) {
                    throw new \Exception('管理员未登录，无法以管理员身份上传');
                }
                return $uploaderId;
            case FileUploaderType::System->value:
                // 系统上传使用配置中的系统上传者ID
                return config('system.uploader_id', (new \app\model\Admin())->getSuperAdminId());
            default:
                throw new \Exception('无效的上传者类型');
        }
    }

    /**
     * 安全获取存储权限，不依赖请求参数
     * @return string
     */
    private function getSecureStoragePermission(): string
    {
        // 默认使用公开权限，确保安全性
        return FileStoragePermission::Public->value;
    }

    /**
     * 获取图片迁移预览
     * @return Response
     */
    public function getMigrationPreview(): Response
    {
        $oldDomain = trim(request()->param('old_domain', ''));
        $newDomain = trim(request()->param('new_domain', ''));
        $page = (int)request()->param('page', 1);

        // 兼容性处理：同时支持list_rows和limit参数
        $listRows = (int)request()->param('list_rows', request()->param('limit', 15));

        // 调试日志
        \think\facade\Log::info("迁移预览请求参数", [
            'old_domain' => $oldDomain,
            'new_domain' => $newDomain,
            'page' => $page,
            'list_rows' => $listRows,
            'limit' => request()->param('limit'),
            'all_params' => request()->param()
        ]);

        if (empty($oldDomain) || empty($newDomain)) {
            return $this->error('请提供旧域名和新域名');
        }

        try {
            $preview = $this->generateMigrationPreview($oldDomain, $newDomain, $page, $listRows);
            return $this->success($preview, '迁移预览生成完成');
        } catch (\Exception $e) {
            return $this->error('预览生成失败：' . $e->getMessage());
        }
    }

    /**
     * 执行图片地址迁移
     * @return Response
     */
    public function migrateUrls(): Response
    {
        $oldDomain = trim(request()->param('old_domain', ''));
        $newDomain = trim(request()->param('new_domain', ''));
        $batchSize = (int)request()->param('batch_size', 1000); // 每批处理1000条
        $maxBatches = (int)request()->param('max_batches', 10); // 最多处理10批

        if (empty($oldDomain) || empty($newDomain)) {
            return $this->error('请提供旧域名和新域名');
        }

        try {
            $result = $this->performUrlMigration($oldDomain, $newDomain, $batchSize, $maxBatches);
            return $this->success($result, '图片地址迁移完成');
        } catch (\Exception $e) {
            return $this->error('迁移失败：' . $e->getMessage());
        }
    }

    /**
     * 生成迁移预览
     * @param string $oldDomain
     * @param string $newDomain
     * @param int $page
     * @param int $listRows
     * @return array
     */
    private function generateMigrationPreview(string $oldDomain, string $newDomain, int $page = 1, int $listRows = 15): array
    {
        $preview = [
            'old_domain' => $oldDomain,
            'new_domain' => $newDomain,
            'affected_files' => [],
            'total_count' => 0,
            'local_count' => 0,
            'other_count' => 0,
            'page' => $page,
            'list_rows' => $listRows,
            'has_more' => false
        ];

        try {
            // 使用BaseModel的fetchData方法，自动处理分页
            $fileModel = new FileModel();

            // 构建查询条件 - 使用闭包函数来构建OR逻辑
            $conditions = function ($query) use ($oldDomain) {
                $query->where('url', 'like', "%{$oldDomain}%")
                    ->whereOr('storage_path', 'like', "%{$oldDomain}%");
            };

            // 使用TP内置的分页查询
            $result = $fileModel->fetchData($conditions, [
                'pageNum' => $page,
                'pageSize' => $listRows
            ]);

            // 如果返回的是分页数据
            if (isset($result['total'])) {
                $preview['total_count'] = $result['total'];
                $preview['has_more'] = ($page * $listRows) < $result['total'];

                // 处理文件列表
                if (isset($result['list']) && is_array($result['list'])) {
                    $localCount = 0;
                    $otherCount = 0;

                    foreach ($result['list'] as $file) {
                        // 分页数据是数组格式，使用数组访问
                        $isLocal = \app\common\service\ImageUrlService::isLocalStorage($file['url']);
                        $newUrl = \app\common\service\ImageUrlService::generateNewUrl($file['url'], $oldDomain, $newDomain);

                        $preview['affected_files'][] = [
                            'table' => 'file',
                            'id' => $file['file_id'],
                            'old_url' => $file['url'],
                            'new_url' => $newUrl,
                            'file_name' => $file['origin_name'],
                            'storage_type' => $file['storage_type'],
                            'is_local' => $isLocal
                        ];

                        if ($isLocal) {
                            $localCount++;
                        } else {
                            $otherCount++;
                        }
                    }

                    $preview['local_count'] = $localCount;
                    $preview['other_count'] = $otherCount;
                    $preview['current_page_count'] = count($preview['affected_files']);
                }
            } else {
                // 如果没有分页参数，返回所有数据（但限制数量）
                // 使用原生查询确保OR逻辑正确
                $files = $fileModel->where(function ($query) use ($oldDomain) {
                    $query->where('url', 'like', "%{$oldDomain}%")
                        ->whereOr('storage_path', 'like', "%{$oldDomain}%");
                })->limit($listRows)->select();

                $preview['total_count'] = $files->count();
                $preview['has_more'] = false;

                $localCount = 0;
                $otherCount = 0;

                foreach ($files as $file) {
                    $isLocal = \app\common\service\ImageUrlService::isLocalStorage($file->url);
                    $newUrl = \app\common\service\ImageUrlService::generateNewUrl($file->url, $oldDomain, $newDomain);

                    $preview['affected_files'][] = [
                        'table' => 'file',
                        'id' => $file->file_id,
                        'old_url' => $file->url,
                        'new_url' => $newUrl,
                        'file_name' => $file->origin_name,
                        'storage_type' => $file->storage_type,
                        'is_local' => $isLocal
                    ];

                    if ($isLocal) {
                        $localCount++;
                    } else {
                        $otherCount++;
                    }
                }

                $preview['local_count'] = $localCount;
                $preview['other_count'] = $otherCount;
                $preview['current_page_count'] = count($preview['affected_files']);
            }

            // 如果总数超过限制，添加警告
            if ($preview['total_count'] > $listRows * 10) {
                $preview['warning'] = "数据量较大（{$preview['total_count']}条），建议分批迁移。当前显示第{$page}页，每页{$listRows}条。";
            }
        } catch (\Exception $e) {
            // 记录错误日志
            \think\facade\Log::error("生成迁移预览失败: " . $e->getMessage(), [
                'old_domain' => $oldDomain,
                'new_domain' => $newDomain,
                'page' => $page,
                'list_rows' => $listRows
            ]);

            // 返回空结果
            $preview['error'] = '生成预览失败：' . $e->getMessage();
        }

        return $preview;
    }

    /**
     * 执行URL迁移
     * @param string $oldDomain
     * @param string $newDomain
     * @param int $batchSize
     * @param int $maxBatches
     * @return array
     */
    private function performUrlMigration(string $oldDomain, string $newDomain, int $batchSize = 1000, int $maxBatches = 10): array
    {
        $stats = [
            'total_files' => 0,
            'migrated_files' => 0,
            'skipped_files' => 0,
            'errors' => [],
            'batches_processed' => 0,
            'current_batch' => 0
        ];

        try {
            $fileModel = new FileModel();

            // 调试日志
            \think\facade\Log::info("开始执行URL迁移", [
                'old_domain' => $oldDomain,
                'new_domain' => $newDomain,
                'batch_size' => $batchSize,
                'max_batches' => $maxBatches
            ]);

            // 先统计总数
            $totalCount = $fileModel->where(function ($query) use ($oldDomain) {
                $query->where('url', 'like', "%{$oldDomain}%")
                    ->whereOr('storage_path', 'like', "%{$oldDomain}%");
            })->count();

            // 调试日志
            \think\facade\Log::info("查询到的文件总数", [
                'total_count' => $totalCount,
                'old_domain' => $oldDomain
            ]);

            $stats['total_files'] = $totalCount;

            if ($totalCount === 0) {
                return $stats;
            }

            // 使用TP的chunk方法进行分批处理，更高效
            $batchCount = 0;
            $processedCount = 0;

            $fileModel->where(function ($query) use ($oldDomain) {
                $query->where('url', 'like', "%{$oldDomain}%")
                    ->whereOr('storage_path', 'like', "%{$oldDomain}%");
            })->chunk($batchSize, function ($files) use (&$stats, &$batchCount, &$processedCount, $oldDomain, $newDomain, $maxBatches) {

                // 检查是否达到最大批次数限制
                if ($batchCount >= $maxBatches) {
                    return false; // 停止chunk
                }

                $batchCount++;
                $stats['current_batch'] = $batchCount;

                // 处理当前批次
                foreach ($files as $file) {
                    try {
                        $migrated = $this->migrateFileUrl($file, $oldDomain, $newDomain);

                        if ($migrated) {
                            $stats['migrated_files']++;
                        } else {
                            $stats['skipped_files']++;
                        }

                        $processedCount++;
                    } catch (\Exception $e) {
                        $stats['errors'][] = "文件ID {$file->file_id}: " . $e->getMessage();
                    }
                }

                $stats['batches_processed'] = $batchCount;

                // 如果还有更多数据但已达到最大批次数，添加提示
                if ($processedCount < $stats['total_files'] && $batchCount >= $maxBatches) {
                    $stats['warning'] = "已达到最大批次数限制（{$maxBatches}批），还有 " . ($stats['total_files'] - $processedCount) . " 条数据未处理。请分批执行迁移。";
                    return false; // 停止chunk
                }
            });

            // 迁移其他表中的图片URL
            $this->migrateOtherTables($oldDomain, $newDomain, $stats);

            // 更新配置文件
            \app\common\service\ImageUrlService::updateLocalImageDomain($newDomain);
        } catch (\Exception $e) {
            // 记录错误日志
            \think\facade\Log::error("执行URL迁移失败: " . $e->getMessage(), [
                'old_domain' => $oldDomain,
                'new_domain' => $newDomain,
                'batch_size' => $batchSize,
                'max_batches' => $maxBatches
            ]);

            $stats['errors'][] = "迁移执行失败: " . $e->getMessage();
        }

        return $stats;
    }

    /**
     * 迁移单个文件的URL
     * @param \think\Model $file
     * @param string $oldDomain
     * @param string $newDomain
     * @return bool 是否成功迁移
     */
    private function migrateFileUrl($file, string $oldDomain, string $newDomain): bool
    {
        // 迁移所有类型的文件URL，不限制存储类型
        $migrated = false;

        // 调试日志
        \think\facade\Log::info("检查文件是否需要迁移", [
            'id' => $file->file_id,
            'file_url' => $file->url,
            'storage_path' => $file->storage_path,
            'old_domain' => $oldDomain,
            'new_domain' => $newDomain
        ]);

        // 迁移URL字段
        if (!empty($file->url) && str_contains($file->url, $oldDomain)) {
            $file->url = \app\common\service\ImageUrlService::generateNewUrl($file->url, $oldDomain, $newDomain);
            $migrated = true;
            \think\facade\Log::info("迁移URL字段", [
                'id' => $file->file_id,
                'old_url' => $file->url,
                'new_url' => $file->url
            ]);
        }

        // 迁移storage_path字段（如果是完整URL）
        if (!empty($file->storage_path) && filter_var($file->storage_path, FILTER_VALIDATE_URL)) {
            if (str_contains($file->storage_path, $oldDomain)) {
                $file->storage_path = \app\common\service\ImageUrlService::generateNewUrl($file->storage_path, $oldDomain, $newDomain);
                $migrated = true;
                \think\facade\Log::info("迁移storage_path字段", [
                    'id' => $file->file_id,
                    'old_storage_path' => $file->storage_path,
                    'new_storage_path' => $file->storage_path
                ]);
            }
        }

        if ($migrated) {
            $file->save();
        }

        return $migrated;
    }

    /**
     * 迁移其他表中的图片URL
     * @param string $oldDomain
     * @param string $newDomain
     * @param array &$stats
     */
    private function migrateOtherTables(string $oldDomain, string $newDomain, array &$stats): void
    {
        // 定义需要检查的字段
        $imageFields = [
            'admin' => ['avatar', 'logo'],
            'system_config' => ['config_value'],
            'menu' => ['icon'],
        ];

        foreach ($imageFields as $table => $fields) {
            try {
                $this->migrateTableUrls($table, $fields, $oldDomain, $newDomain, $stats);
            } catch (\Exception $e) {
                $stats['errors'][] = "表 {$table}: " . $e->getMessage();

                // 记录错误日志
                \think\facade\Log::error("迁移表 {$table} 失败: " . $e->getMessage(), [
                    'table' => $table,
                    'old_domain' => $oldDomain,
                    'new_domain' => $newDomain
                ]);
            }
        }
    }

    /**
     * 迁移指定表的URL
     * @param string $table
     * @param array $fields
     * @param string $oldDomain
     * @param string $newDomain
     * @param array &$stats
     */
    private function migrateTableUrls(string $table, array $fields, string $oldDomain, string $newDomain, array &$stats): void
    {
        $modelClass = "\\app\\model\\" . ucfirst($table);
        if (!class_exists($modelClass)) {
            return;
        }

        $model = new $modelClass();

        // 使用chunk方法分批处理，避免内存溢出
        $model->chunk(1000, function ($records) use ($table, $fields, $oldDomain, $newDomain, &$stats) {
            foreach ($records as $record) {
                $updated = false;

                foreach ($fields as $field) {
                    if (isset($record->$field) && !empty($record->$field)) {
                        $value = $record->$field;

                        // 检查是否为URL且需要迁移（不限制文件类型）
                        if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                            if (\app\common\service\ImageUrlService::needsMigration($value, $oldDomain)) {
                                $record->$field = \app\common\service\ImageUrlService::generateNewUrl($value, $oldDomain, $newDomain);
                                $updated = true;
                            }
                        }
                    }
                }

                if ($updated) {
                    try {
                        $record->save();
                        $stats['migrated_files']++;
                    } catch (\Exception $e) {
                        $stats['errors'][] = "表 {$table} 记录ID {$record->getKey()}: " . $e->getMessage();
                    }
                }
            }
        });
    }

    /**
     * 判断是否为图片URL
     * @param string $url
     * @return bool
     */
    private function isImageUrl(string $url): bool
    {
        // 检查是否为有效的URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // 获取文件扩展名
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

        // 图片文件扩展名
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];

        return in_array($extension, $imageExtensions);
    }
}
