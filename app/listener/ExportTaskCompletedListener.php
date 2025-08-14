<?php

namespace app\listener;

use app\common\service\MessageService;
use app\event\ExportTaskCompleted;
use think\facade\Log;

class ExportTaskCompletedListener
{
    protected $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * 处理导出任务完成事件
     *
     * @param  ExportTaskCompleted  $event
     * @return void
     */
    public function handle(ExportTaskCompleted $event)
    {
        try {
            // 发送任务完成消息给创建者
            $result = $this->messageService->sendTaskCompletedMessage(
                $event->taskName,
                $event->creatorId,
                $event->filePath
            );

            if ($result) {
                Log::info("导出任务完成消息发送成功：任务ID {$event->taskId}，创建者ID {$event->creatorId}");
            } else {
                Log::error("导出任务完成消息发送失败：任务ID {$event->taskId}");
            }
        } catch (\Exception $e) {
            Log::error('处理导出任务完成事件失败：' . $e->getMessage());
        }
    }
}