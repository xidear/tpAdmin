<?php
namespace app\controller\admin\websocket;

use think\facade\Cache;
use Workerman\Timer;

class Message
{
    // 全局Redis频道
    public static string $redisChannel = 'msg_channel';

    /**
     * 初始化定时推送
     */
    public static function initTimer(\Workerman\Worker $worker): void
    {
        echo "定时任务已初始化，将每30秒推送一次示例消息\n".root_path("app");
        // 每30秒推送随机消息（包含三种类别）
        Timer::add(30, function () use ($worker) {
            // 随机生成三种类别之一的消息
//            $categoryTypes = ['message', 'chat', 'todo'];
//            $category = $categoryTypes[array_rand($categoryTypes)];
//                不再发送随机数据
//            $msg = self::generateRandomMsg($category);
//            self::broadcast($worker, $msg);
        });
    }

    /**
     * 生成随机消息（根据类别生成不同内容）
     */
    private static function generateRandomMsg(string $category): array {
        // 不同类别对应不同的消息类型和内容
        switch ($category) {
            case 'message': // 通知类
                $subTypes = ['system', 'notice'];
                $subType = $subTypes[array_rand($subTypes)];
                return [
                    'type' => 'message',
                    'data' => [
                        'msgType' => $subType,
                        'title' => match ($subType) {
                            'system' => '系统运行正常',
                            'notice' => '新公告发布：请及时查看'
                        },
                        'date' => rand(1, 60) . '分钟前'
                    ]
                ];
                break;

            case 'chat': // 消息类（含客服消息）
                $subTypes = ['interactive', 'customer'];
                $subType = $subTypes[array_rand($subTypes)];
                return [
                    'type' => 'chat',
                    'data' => [
                        'msgType' => $subType,
                        'title' => match ($subType) {
                            'interactive' => '用户评论了你的文章',
                            'customer' => '客户咨询：这个产品有货吗？'
                        },
                        'date' => rand(1, 60) . '分钟前'
                    ]
                ];
                break;

            case 'todo': // 待办类
                return [
                    'type' => 'todo',
                    'data' => [
                        'msgType' => 'task',
                        'title' => '待处理任务：' . ['订单审核', '退款处理', '内容审核'][array_rand(['订单审核', '退款处理', '内容审核'])],
                        'date' => rand(1, 60) . '分钟前'
                    ]
                ];
                break;
        }
    }

    /**
     * 广播消息到当前进程的客户端
     */
    public static function broadcast(\Workerman\Worker $worker, array $msg): void
    {
        $message = json_encode($msg);
        echo "生成示例消息：{$message}\n"; // 新增日志
        foreach ($worker->connections as $conn) {
            if ($conn->getStatus() === \Workerman\Connection\TcpConnection::STATUS_ESTABLISHED) {
                echo "开始推送\n"; // 新增日志
                $conn->send($message);
                echo "推送结束\n"; // 新增日志
            }
        }
    }

    /**
     * 推送数据库消息到Redis
     */
    public static function pushFromDb(array $data): void
    {
        // 确保数据库消息包含类别信息（message/chat/todo）
        $message = json_encode([
            'type' => $data['category'], // 这里的category对应前端的三个类别
            'data' => [
                'msgType' => $data['type'],
                'title' => $data['content'],
                'date' => '刚刚'
            ]
        ]);

        if (str_contains(PHP_OS, 'WIN')) {
            $fp = fsockopen('127.0.0.1', 6379, $errno, $errstr, 3);
            if ($fp) {
                fwrite($fp, "PUBLISH " . self::$redisChannel . " " . strlen($message) . "\r\n" . $message . "\r\n");
                fclose($fp);
            }
        } else {
            $redis = Cache::store('redis')->handler();
            $redis->publish(self::$redisChannel, $message);
        }
    }
}
