<?php



// 事件定义文件
return [
    'bind'      => [
          // 导出任务完成事件
        'ExportTaskCompleted' => 'app\event\ExportTaskCompleted',
    ],

    'listen'    => [
        'AppInit'  => [],
        'HttpRun'  => [],
        'HttpEnd'  => [],
        'LogLevel' => [],
        'LogWrite' => [],
         // 导出任务完成监听器
        'ExportTaskCompleted' => [
            'app\listener\ExportTaskCompletedListener',
        ],
        // 管理员信息更新监听器
        'AdminInfoUpdated'=>[
            \app\listener\AdminInfoUpdated::class
        ],
          \app\event\RegionChanged::class => [
            \app\listener\RegionChangedListener::class
        ],
    ],

    'subscribe' => [
    ],
];
