<?php

namespace app\service\export;

use think\Model;
use think\db\Query;
use app\service\export\ExcelExporter;

class ExportService
{
    /**
     * 导出主入口
     * @param Model|Query $query 查询构造器（支持 where join 等复杂操作）
     * @param array $headers 表头配置 [['field'=>'字段1','title'=>'标题1'], ...]
     * @param string $fileName 文件名（不带扩展名）
     * @param int $limit 单批导出条数（超出即分批）
     * @param string $format xlsx|xls
     */
    public static function export(
        Model|Query $query,
        array       $headers,
        string      $fileName = '导出数据',
        int         $limit = 1000,
        string      $format = 'xlsx'
    ): void
    {
        (new ExcelExporter($query, $headers, $fileName, $limit, $format))->export();
    }
}
