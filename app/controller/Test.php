<?php
namespace app\controller;

class Test
{
    public function index()
    {
        $data = [
            'message' => 'URL测试任务执行成功',
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->header('user-agent')
        ];
        
        // 记录访问日志
        $logMessage = json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
        if(!file_exists(runtime_path() . 'log/')) {
            mkdir(runtime_path() . 'log/',0777, true);
        }
        file_put_contents(runtime_path() . 'log/url_test.log', $logMessage, FILE_APPEND);
        
        return json($data);
    }
}