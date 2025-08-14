<?php
namespace app\controller;

use app\common\BaseController;
use app\common\service\export\ExportJob;
use app\model\Admin;
use think\facade\Queue;

class ExportTaskBak extends BaseController
{
    public function export_example()
    {
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

        // 或者一级表头
        // $headers = [
        //     ['label' => 'ID', 'field' => 'id'],
        //     ['label' => '姓名', 'field' => 'name'],
        //     ['label' => '邮箱', 'field' => 'email']
        // ];

        // 执行导出
        return $query->exportData($headers, '用户数据', 'xlsx');
    }

    /**
     *
     * 检查导出任务状态
     */
    public function exportStatus($jobId)
    {
        $task = \app\common\model\ExportTask::where('job_id', $jobId)->find();

        if (!$task) {
            return $this->error("任务不存在");
        }

        $data
            = [
            'job_id' => $task->job_id,
            'status' => $task->status,
            'progress' => $task->progress,
            'message' => $this->getStatusMessage($task->status, $task->error_msg),
            'queue_position' => $task->getQueuePosition(),
            'file_path' => $task->status == \app\common\model\ExportTask::STATUS_SUCCESS
                ? '/exports/' . $task->file_path
                : null
        ];
        return $this->success($data);
    }

    // 重启失败的导出任务
    public function restartExport($jobId)
    {
        $task = \app\common\model\ExportTask::where('job_id', $jobId)->find();

        if (!$task) {
            return $this->error("任务不存在");

        }

        if ($task->status != \app\common\model\ExportTask::STATUS_FAILED) {
            return $this->error("只有失败的任务可以重启");
        }

        // 创建新任务，复制原任务的参数
        $newJobId = uniqid('export_');
        $newTask = $task->clone();
        $newTask->job_id = $newJobId;
        $newTask->status = \app\common\model\ExportTask::STATUS_PENDING;
        $newTask->created_at = time();
        $newTask->started_at = null;
        $newTask->completed_at = null;
        $newTask->error_msg = null;
        $newTask->progress = 0;
        $newTask->save();

        Queue::push(ExportJob::class, $newJobId, 'export');


        return $this->success( ['job_id' => $newJobId]);
    }

    protected function getStatusMessage($status, $errorMsg = '')
    {
        $messages = [
            \app\common\model\ExportTask::STATUS_PENDING => '排队中',
            \app\common\model\ExportTask::STATUS_PROCESSING => '处理中',
            \app\common\model\ExportTask::STATUS_SUCCESS => '导出成功',
            \app\common\model\ExportTask::STATUS_FAILED => '导出失败: ' . $errorMsg,
            \app\common\model\ExportTask::STATUS_CANCELLED => '已取消'
        ];

        return $messages[$status] ?? '未知状态';
    }
}