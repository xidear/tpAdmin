<?php
namespace app\common\service\export;

use app\common\model\ExportTask;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\facade\Db;
use think\facade\Queue;

class ExportService
{
    /**
     * 估算查询结果数量
     */
    public function estimateCount(\Closure $queryCallback)
    {
        $query = $queryCallback();
        $sql = $query->fetchSql()->select();

        $explainSql = "EXPLAIN " . $sql;
        $result = Db::query($explainSql);

        return $result[0]['rows'] ?? 0;
    }

    /**
     * 创建队列任务
     */
    public function createQueueTask($modelClass, $query, $headers, $filename, $type = 'csv')
    {
        // 获取查询条件
        $queryConditions = $this->getQueryConditions($query);

        // 生成查询哈希，用于检测重复任务
        $queryHash = ExportTask::generateQueryHash(
            $modelClass,
            $queryConditions,
            $headers,
            $type
        );

        // 检查是否有可复用的任务
        $reusableTask = ExportTask::findReusableTask($queryHash);
        if ($reusableTask) {
            return [
                'job_id' => $reusableTask->job_id,
                'message' => '发现可复用的导出结果',
                'reused' => true,
                'status' => $reusableTask->status,
                'file_path' => $reusableTask->file_path
            ];
        }

        // 创建新任务
        $jobId = uniqid('export_');
        $task = new ExportTask();
        $task->job_id = $jobId;
        $task->model_class = $modelClass;
        $task->query_conditions = $queryConditions;
        $task->headers = $headers;
        $task->filename = $filename;
        $task->file_type = $type;
        $task->query_hash = $queryHash;
        $task->status = ExportTask::STATUS_PENDING;
        $task->created_at = time();
        $task->created_by = $this->getCurrentUserId(); // 需要实现获取当前用户ID的逻辑
        $task->save();

        Queue::push(ExportJob::class, $jobId, 'export');

        return [
            'job_id' => $jobId,
            'message' => '导出任务已加入队列，请稍后查询结果',
            'reused' => false
        ];
    }

    /**
     * 直接导出
     */
    public function directExport($query, $headers, $filename = 'export', $type = 'xlsx')
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 解析表头（兼容多级和一级表头）
        $parseResult = $this->parseHeaders($headers);

        // 设置表头
        foreach ($parseResult['headers'] as $header) {
            $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($header['col']) . $header['row'];
            $sheet->setCellValue($cellCoordinate, $header['value']);

            if ($header['mergeCols'] > 1) {
                $endCol = $header['col'] + $header['mergeCols'] - 1;
                $startCellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($header['col']) . $header['row'];
                $endCellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($endCol) . $header['row'];
                $sheet->mergeCells($startCellCoordinate . ':' . $endCellCoordinate);
            }
        }

        // 填充数据
        $rowIndex = $parseResult['maxRow'] + 1;
        foreach ($query->cursor() as $item) {
            $colIndex = 1;
            $item = is_object($item) ? $item->toArray() : $item;

            foreach ($parseResult['fieldMap'] as $field) {
                if ($field) {
                    $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . $rowIndex;
                    $sheet->setCellValue($cellCoordinate, $item[$field] ?? '');
                }
                $colIndex++;
            }

            $rowIndex++;
        }

        // 输出文件
        $this->outputFile($spreadsheet, $filename, $type);
    }

    /**
     * 解析表头（兼容多级和一级）
     */
    public function parseHeaders(array $headers, $currentRow = 1, $currentCol = 1)
    {
        $result = [
            'headers' => [],
            'maxRow' => $currentRow,
            'fieldMap' => []
        ];

        foreach ($headers as $header) {
            // 处理一级表头格式（兼容旧格式）
            if (is_string($header)) {
                $header = ['label' => $header, 'field' => $header];
            }

            // 处理普通表头
            if (!isset($header['children'])) {
                $result['headers'][] = [
                    'col' => $currentCol,
                    'row' => $currentRow,
                    'value' => $header['label'],
                    'mergeCols' => 1
                ];
                $result['fieldMap'][] = $header['field'] ?? null;
                $currentCol++;
                continue;
            }

            // 处理有子节点的表头
            $childResult = $this->parseHeaders($header['children'], $currentRow + 1, $currentCol);

            // 添加当前表头
            $result['headers'][] = [
                'col' => $currentCol,
                'row' => $currentRow,
                'value' => $header['label'],
                'mergeCols' => count($childResult['fieldMap'])
            ];

            // 合并子节点结果
            $result['headers'] = array_merge($result['headers'], $childResult['headers']);
            $result['fieldMap'] = array_merge($result['fieldMap'], $childResult['fieldMap']);
            $result['maxRow'] = max($result['maxRow'], $childResult['maxRow']);
            $currentCol += count($childResult['fieldMap']);
        }

        return $result;
    }

    /**
     * 从查询对象中提取查询条件
     */
    protected function getQueryConditions($query)
    {
        // 这里需要根据实际情况实现，提取查询条件
        // 包括where、with、order等
        return [
            'where' => $query->getOptions('where'),
            'order' => $query->getOptions('order'),
            'with' => $query->getOptions('with'),
            'field' => $query->getOptions('field'),
        ];
    }

    /**
     * 输出文件
     */
    protected function outputFile(Spreadsheet $spreadsheet, string $filename, string $type)
    {
        if ($type === 'csv') {
            header('Content-Type: text/csv');
        } else {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        }
        header("Content-Disposition: attachment;filename=\"{$filename}.{$type}\"");
        header('Cache-Control: max-age=0');

        $writer = $type === 'csv' ? new Csv($spreadsheet) : new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * 获取当前用户ID
     */
    protected function getCurrentUserId()
    {
        // 实现获取当前用户ID的逻辑
        return 0; // 默认为0表示系统
    }
}