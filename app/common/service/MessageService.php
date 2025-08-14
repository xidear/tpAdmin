<?php

namespace app\common\service;

use app\common\BaseService;
use app\controller\admin\websocket\Message;
use think\facade\Log;

/**
 * 消息服务类 - 统一处理消息发送
 */
class MessageService extends BaseService
{
    /**
     * 发送消息
     * @param string $category 消息类别(message/chat/todo)
     * @param string $type 消息类型
     * @param string $content 消息内容
     * @param int $receiverId 接收者ID
     * @param array $extraData 额外数据
     * @return bool
     */
    public function sendMessage(string $category, string $type, string $content, int $receiverId, array $extraData = []): bool
    {
        try {
            // 准备消息数据
            $messageData = array_merge([
                'category' => $category,
                'type' => $type,
                'content' => $content,
                'receiver_id' => $receiverId,
                'send_time' => date('Y-m-d H:i:s')
            ], $extraData);

            // 保存到数据库（如果需要持久化）
            $this->saveToDatabase($messageData);

            // 推送到Redis
            $this->pushToRedis($messageData);

            return true;
        } catch (\Exception $e) {
            Log::error('发送消息失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 发送系统通知
     * @param string $content 消息内容
     * @param int $receiverId 接收者ID
     * @param array $extraData 额外数据
     * @return bool
     */
    public function sendSystemMessage(string $content, int $receiverId, array $extraData = []): bool
    {
        return $this->sendMessage('message', 'system', $content, $receiverId, $extraData);
    }

    /**
     * 发送任务完成通知
     * @param string $taskName 任务名称
     * @param int $receiverId 接收者ID
     * @param string $filePath 文件路径
     * @return bool
     */
    public function sendTaskCompletedMessage(string $taskName, int $receiverId, string $filePath = ''): bool
    {
        $content = "任务【{$taskName}】已成功完成";
        if ($filePath) {
            $content .= "，文件已生成";
        }
        
        return $this->sendMessage('message', 'system', $content, $receiverId, [
            'task_name' => $taskName,
            'file_path' => $filePath
        ]);
    }

    /**
     * 保存消息到数据库
     * @param array $data 消息数据
     */
    private function saveToDatabase(array $data): void
    {
        // 这里可以根据需要创建Message模型来保存消息到数据库
        // 暂时只推送到Redis，不保存到数据库
    }

    /**
     * 推送消息到Redis
     * @param array $data 消息数据
     */
    private function pushToRedis(array $data): void
    {
        try {
            Message::pushFromDb($data);
        } catch (\Exception $e) {
            Log::error('推送消息到Redis失败：' . $e->getMessage());
        }
    }
}