<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\common\service\FileUploadService;
use app\common\service\FileMigrationService;
use app\model\File as FileModel;
use app\request\admin\file\Upload;
use think\App;
use think\Response;

class File extends BaseController
{
    protected FileUploadService $fileUploadService;
    protected FileMigrationService $fileMigrationService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->fileUploadService = new FileUploadService();
        $this->fileMigrationService = new FileMigrationService();
    }

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
        $file = (new FileModel())->fetchOne($file_id);
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
        try {
            // 获取上传的文件
            $file = $this->request->file('file');
            if (!$file) {
                return $this->error('请选择上传的文件');
            }

            // 构建上传选项
            $options = [
                'file_type' => $this->request->param('file_type', 'all'),
                'upload_dir' => $this->request->param('upload_dir', 'uploads'),
                'permission' => $this->request->param('permission', 'public')
            ];

            // 使用服务上传文件（管理端上下文）
            $result = $this->fileUploadService->upload($file, $options, 'admin');

            return $this->success($result, '文件上传成功');
        } catch (\Exception $e) {
            return $this->error('文件上传失败: ' . $e->getMessage());
        }
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
        $listRows = (int)request()->param('list_rows', request()->param('limit', 15));

        if (empty($oldDomain) || empty($newDomain)) {
            return $this->error('请提供旧域名和新域名');
        }

        try {
            $preview = $this->fileMigrationService->generateMigrationPreview($oldDomain, $newDomain, $page, $listRows);
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
        $batchSize = (int)request()->param('batch_size', 1000);
        $maxBatches = (int)request()->param('max_batches', 10);

        if (empty($oldDomain) || empty($newDomain)) {
            return $this->error('请提供旧域名和新域名');
        }

        try {
            $result = $this->fileMigrationService->performUrlMigration($oldDomain, $newDomain, $batchSize, $maxBatches);
            return $this->success($result, '图片地址迁移完成');
        } catch (\Exception $e) {
            return $this->error('迁移失败：' . $e->getMessage());
        }
    }
}
