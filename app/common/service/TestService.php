<?php
namespace app\common\service;

use app\common\BaseService;

class TestService  extends BaseService
{
    public function testMethod(): string
    {
        $logMessage = "PHP测试任务执行时间: " . date('Y-m-d H:i:s') . "\n";
        $logMessage .= "执行用户: " . get_current_user() . "\n";
        $logMessage .= "PHP版本: " . phpversion() . "\n";
        $logMessage .= "内存使用: " . memory_get_usage(true) . " bytes\n";
        $logDir = runtime_path() . 'log';  // 改为logs

         if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        // 写入日志文件
        file_put_contents($logDir. '/php_test.log', $logMessage, FILE_APPEND);
        
        return $logMessage;
    }
}