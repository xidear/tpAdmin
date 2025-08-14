<?php
namespace app\controller;

use app\common\BaseController;
use app\common\model\ExportTask as ExportTaskModel;
use app\common\service\export\ExportService;
use app\model\Admin;
use app\model\Permission;
use think\exception\ValidateException;
use think\facade\Log;
use think\facade\Queue;

class ExportTask extends BaseController
{
    /**
     * 导出示例（带权限控制）
     */
    public function export_example()
    {
        try {
            // 权限规则（需与权限表中rule字段一致）
            $permissionRule = 'adminapi/export/admin';
            
            // 权限校验
            $permissionService = new PermissionService();
            $currentUserId = $this->getCurrentUserId();
            if (!$permissionService->hasRulePermission($currentUserId, $permissionRule)) {
                return $this->error("无权限执行此导出操作");
            }

            // 构建查询条件
            $query = Admin::where('status', 1)
                ->where('create_time', '>', strtotime('-1 year'));

            // 定义表头（支持多级）
            $headers = [
                [
                    'label' => '基本信息',
                    'children' => [
                        ['label' => 'ID', 'field' => 'id'],
                        ['label' => '姓名', 'field' => 'name'],
                        ['label' => '邮箱', 'field' => 'email']
                    ]
                ],
                [
                    'label' => '账户信息',
                    'children' => [
                        ['label' => '注册时间', 'field' => 'create_time'],
                        ['label' => '状态', 'field' => 'status']
                    ]
                ]
            ];

            // 执行导出
            $exportService = new ExportService();
            $result = $exportService->createQueueTask(
                Admin::class,
                $query,
                $headers,
                '管理员数据',
                'xlsx',
                $permissionRule
            );

            return $this->success($result['message'], $result);
        } catch (ValidateException $e) {
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            Log::error("导出任务创建失败: " . $e->getMessage());
            return $this->error("系统异常，导出任务创建失败");
        }
    }

    /**
     * 任务列表（当前用户可见的任务）
     */
    public function index()
    {
        try {
            $currentUserId = $this->getCurrentUserId();
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 10, 'intval');

            // 获取用户权限规则
            $permissionService = new PermissionService();
            $userRules = $permissionService->getUserRules($currentUserId);

            // 构建查询
            $query = ExportTaskModel::where(function ($query) use ($currentUserId, $userRules) {
                // 自己创建的任务或有权限的任务
                $query->WhereIn('permission_rule', $userRules);
            });

            // 状态筛选
            $status = $this->request->param('status');
            if (!empty($status)) {
                $query->where('status', $status);
            }

            // 分页查询
            $tasks = $query->order('created_at', 'desc')
                ->paginate([
                    'page' => $page,
                    'list_rows' => $limit,
                    'var_page' => 'page'
                ]);

            return $this->success($tasks);
        } catch (\Exception $e) {
            Log::error("任务列表查询失败: " . $e->getMessage());
            return $this->error("任务列表查询失败");
        }
    }

    /**
     * 检查导出任务状态
     */
    public function exportStatus($jobId)
    {
        try {
            $task = ExportTaskModel::where('job_id', $jobId)->find();
            if (!$task) {
                return $this->error("任务不存在");
            }

            // 权限校验
            if (!$this->hasTaskPermission($task)) {
                return $this->error("无权限查看此任务");
            }

            $data = [
                'job_id' => $task->job_id,
                'status' => $task->status,
                'progress' => $task->progress,
                'total_rows' => $task->total_rows ?? 0,
                'exported_rows' => $task->exported_rows ?? 0,
                'message' => $this->getStatusMessage($task->status, $task->error_msg),
                'queue_position' => $task->getQueuePosition(),
                'file_path' => $task->status == ExportTaskModel::STATUS_SUCCESS
                    ? '/exports/' . $task->file_path
                    : null
            ];
            
            return $this->success($data);
        } catch (\Exception $e) {
            Log::error("任务状态查询失败: " . $e->getMessage());
            return $this->error("任务状态查询失败");
        }
    }

    /**
     * 重启失败的导出任务
     */
    public function restartExport($jobId)
    {
        try {
            $task = ExportTaskModel::where('job_id', $jobId)->find();
            if (!$task) {
                return $this->error("任务不存在");
            }

            // 权限校验
            if (!$this->hasTaskPermission($task)) {
                return $this->error("无权限重启此任务");
            }

            // 状态校验
            if ($task->status != ExportTaskModel::STATUS_FAILED) {
                return $this->error("只有失败的任务可以重启");
            }

            // 克隆任务
            $newJobId = uniqid('export_');
            $newTask = $task->clone();
            $newTask->job_id = $newJobId;
            $newTask->status = ExportTaskModel::STATUS_PENDING;
            $newTask->created_at = time();
            $newTask->started_at = null;
            $newTask->completed_at = null;
            $newTask->error_msg = null;
            $newTask->progress = 0;
            $newTask->expire_at = time() + 86400;

            // 刷新数据版本
            $exportService = new ExportService();
            $query = (new $task->model_class())->buildQueryFromConditions($task->query_conditions);
            $newTask->data_count = $exportService->estimateDataCount($query);
            $newTask->data_version = md5($newTask->data_count . $exportService->getLastUpdateTime($task->model_class));

            $newTask->save();

            // 推送队列
            Queue::push(\app\common\service\export\ExportJob::class, $newJobId, 'export');

            return $this->success(['job_id' => $newJobId], "任务已重启");
        } catch (\Exception $e) {
            Log::error("任务重启失败: " . $e->getMessage());
            return $this->error("任务重启失败: " . $e->getMessage());
        }
    }

    /**
     * 中止排队中的任务
     */
    public function cancelExport($jobId)
    {
        try {
            $task = ExportTaskModel::where('job_id', $jobId)->find();
            if (!$task) {
                return $this->error("任务不存在");
            }

            // 权限校验
            if (!$this->hasTaskPermission($task)) {
                return $this->error("无权限中止此任务");
            }

            // 状态校验
            if ($task->status != ExportTaskModel::STATUS_PENDING) {
                return $this->error("只有排队中的任务可以中止");
            }

            // 更新状态
            $task->status = ExportTaskModel::STATUS_CANCELLED;
            $task->completed_at = time();
            $task->save();

            return $this->success([], "任务已中止");
        } catch (\Exception $e) {
            Log::error("任务中止失败: " . $e->getMessage());
            return $this->error("任务中止失败");
        }
    }

    /**
     * 删除任务（仅允许删除已完成/失败/取消的任务）
     */
    public function deleteExport($jobId)
    {
        try {
            $task = ExportTaskModel::where('job_id', $jobId)->find();
            if (!$task) {
                return $this->error("任务不存在");
            }

            // 权限校验
            if (!$this->hasTaskPermission($task)) {
                return $this->error("无权限删除此任务");
            }

            // 状态校验
            if (!in_array($task->status, [
                ExportTaskModel::STATUS_SUCCESS,
                ExportTaskModel::STATUS_FAILED,
                ExportTaskModel::STATUS_CANCELLED
            ])) {
                return $this->error("仅允许删除已完成、失败或已取消的任务");
            }

            // 删除任务记录（可选：同时删除文件）
            $filePath = app()->getRootPath() . 'public/exports/' . $task->file_path;
            if ($task->status == ExportTaskModel::STATUS_SUCCESS && file_exists($filePath)) {
                unlink($filePath); // 删除文件
            }

            $task->delete();

            return $this->success([], "任务已删除");
        } catch (\Exception $e) {
            Log::error("任务删除失败: " . $e->getMessage());
            return $this->error("任务删除失败");
        }
    }

    /**
     * 下载导出文件
     */
    public function downloadExport($jobId)
    {
        try {
            $task = ExportTaskModel::where('job_id', $jobId)->find();
            if (!$task) {
                return $this->error("任务不存在");
            }

            // 权限校验
            if (!$this->hasTaskPermission($task)) {
                return $this->error("无权限下载此文件");
            }

            // 状态校验
            if ($task->status != ExportTaskModel::STATUS_SUCCESS || empty($task->file_path)) {
                return $this->error("任务未完成或文件不存在");
            }

            // 下载文件
            $filePath = app()->getRootPath() . 'public/exports/' . $task->file_path;
            if (!file_exists($filePath)) {
                return $this->error("文件已被删除");
            }

            return download($filePath, $task->filename . '.' . $task->file_type);
        } catch (\Exception $e) {
            Log::error("文件下载失败: " . $e->getMessage());
            return $this->error("文件下载失败");
        }
    }

    /**
     * 权限校验：判断用户是否有权操作任务
     */
    protected function hasTaskPermission(ExportTaskModel $task): bool
    {
        $currentUserId = request()->adminId;
        if ($task->created_by == $currentUserId) {
            return true;
        }

        $permissionService = new Permission();
        return $permissionService->hasRulePermission($currentUserId, $task->permission_rule);
    }

    /**
     * 状态消息映射
     */
    protected function getStatusMessage($status, $errorMsg = '')
    {
        $messages = [
            ExportTaskModel::STATUS_PENDING => '排队中',
            ExportTaskModel::STATUS_PROCESSING => '处理中',
            ExportTaskModel::STATUS_SUCCESS => '导出成功',
            ExportTaskModel::STATUS_FAILED => '导出失败: ' . $errorMsg,
            ExportTaskModel::STATUS_CANCELLED => '已取消'
        ];

        return $messages[$status] ?? '未知状态';
    }

    /**
     * 获取当前登录用户ID
     */
    protected function getCurrentUserId()
    {
        // 根据实际登录逻辑获取用户ID（示例）
        return $this->request->user_id ?? 0;
    }
}