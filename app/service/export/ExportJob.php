<?php
namespace app\common\job;

use think\queue\Job;
use app\common\model\ExportTask;
use app\common\service\ExportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class ExportJob
{
    public function fire(Job $job, $jobId)
    {
        $task = ExportTask::where('job_id', $jobId)->find();

        if (!$task) {
            $job->delete();
            return;
        }

        try {
            // 更新任务状态为处理中
            $task->status = ExportTask::STATUS_PROCESSING;
            $task->started_at = time();
            $task->save();

            // 执行导出
            $exportService = new ExportService();
            $filePath = $this->performExport($task, $exportService);

            // 更新任务状态为成功
            $task->status = ExportTask::STATUS_SUCCESS;
            $task->file_path = $filePath;
            $task->completed_at = time();
            $task->progress = 100;
            $task->save();

        } catch (\Exception $e) {
            // 更新任务状态为失败
            $task->status = ExportTask::STATUS_FAILED;
            $task->error_msg = $e->getMessage() . "\n" . $e->getTraceAsString();
            $task->completed_at = time();
            $task->save();
        }

        // 删除任务
        $job->delete();
    }

    /**
     * 执行实际导出
     */
    protected function performExport(ExportTask $task, ExportService $exportService)
    {
        $modelClass = $task->model_class;
        $queryConditions = $task->query_conditions;
        $headers = $task->headers;
        $filename = $task->filename;
        $type = $task->file_type;

        // 重建查询
        $model = new $modelClass();
        $query = $this->rebuildQuery($model, $queryConditions);

        // 获取总记录数
        $totalRows = $query->count();
        $task->total_rows = $totalRows;
        $task->save();

        // 创建临时文件
        $savePath = $this->getExportSavePath();
        $fileExt = $type === 'csv' ? 'csv' : 'xlsx';
        $fileName = $filename . '_' . date('YmdHis') . '.' . $fileExt;
        $fullPath = $savePath . $fileName;

        // 创建表格
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 解析表头
        $parseResult = $exportService->parseHeaders($headers);

        // 设置表头
        foreach ($parseResult['headers'] as $header) {
            $sheet->setCellValueByColumnAndRow($header['col'], $header['row'], $header['value']);

            if ($header['mergeCols'] > 1) {
                $endCol = $header['col'] + $header['mergeCols'] - 1;
                $sheet->mergeCellsByColumnAndRow(
                    $header['col'], $header['row'],
                    $endCol, $header['row']
                );
            }
        }

        // 填充数据
        $rowIndex = $parseResult['maxRow'] + 1;
        $exportedRows = 0;
        $batchSize = 1000;

        foreach ($query->cursor() as $item) {
            $colIndex = 1;
            $item = is_object($item) ? $item->toArray() : $item;

            foreach ($parseResult['fieldMap'] as $field) {
                if ($field) {
                    $sheet->setCellValueByColumnAndRow($colIndex, $rowIndex, $item[$field] ?? '');
                }
                $colIndex++;
            }

            $rowIndex++;
            $exportedRows++;

            // 每处理一定数量更新进度
            if ($exportedRows % $batchSize == 0) {
                $progress = min(99, intval($exportedRows / $totalRows * 100));
                $task->progress = $progress;
                $task->exported_rows = $exportedRows;
                $task->save();

                // 释放内存
                gc_collect_cycles();
            }
        }

        // 保存文件
        $writer = $type === 'csv' ? new Csv($spreadsheet) : new Xlsx($spreadsheet);
        $writer->save($fullPath);

        // 更新最终进度
        $task->progress = 100;
        $task->exported_rows = $exportedRows;
        $task->save();

        return $fileName;
    }

    /**
     * 重建查询对象
     */
    protected function rebuildQuery($model, $conditions)
    {
        $query = clone $model;

        // 应用查询条件
        if (!empty($conditions['where'])) {
            $query->where($conditions['where']);
        }

        if (!empty($conditions['order'])) {
            $query->order($conditions['order']);
        }

        if (!empty($conditions['with'])) {
            $query->with($conditions['with']);
        }

        if (!empty($conditions['field'])) {
            $query->field($conditions['field']);
        }

        return $query;
    }

    /**
     * 获取导出文件保存路径
     */
    protected function getExportSavePath()
    {
        $savePath = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'exports' . DIRECTORY_SEPARATOR;

        if (!is_dir($savePath)) {
            mkdir($savePath, 0755, true);
        }

        return $savePath;
    }
}