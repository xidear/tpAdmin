<?php
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class TestCommand extends Command
{
    protected function configure()
    {
        $this->setName('test:command')
             ->setDescription('测试命令行任务执行');
    }

    protected function execute(Input $input, Output $output)
    {
        $message = "命令行测试任务执行时间: " . date('Y-m-d H:i:s') . "\n";
        $message .= "当前目录: " . getcwd() . "\n";
        $message .= "执行用户: " . get_current_user() . "\n";
        $message .= "PHP版本: " . phpversion() . "\n";
        $logDir = runtime_path() . 'log';  // 改为logs
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // 写入日志文件
        file_put_contents($logDir . '/command_test.log', $message, FILE_APPEND);
        
        $output->writeln($message);
        return 0;
    }
}