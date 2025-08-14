<?php

namespace app\event;

/**
 * 导出任务完成事件
 */
class ExportTaskCompleted
{
    /** @var int 任务ID */
    public $taskId;
    
    /** @var string 任务名称 */
    public $taskName;
    
    /** @var int 创建者ID */
    public $creatorId;
    
    /** @var string 文件路径 */
    public $filePath;
    
    /** @var int 导出记录数 */
    public $exportedRows;

    public function __construct(int $taskId, string $taskName, int $creatorId, string $filePath = '', int $exportedRows = 0)
    {
        $this->taskId = $taskId;
        $this->taskName = $taskName;
        $this->creatorId = $creatorId;
        $this->filePath = $filePath;
        $this->exportedRows = $exportedRows;
    }
}