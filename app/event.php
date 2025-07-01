<?php
// 事件定义文件
return [
    'bind'      => [
    ],

    'listen'    => [
        'AppInit'  => [],
        'HttpRun'  => [],
        'HttpEnd'  => [],
        'LogLevel' => [],
        'LogWrite' => [],
        'AdminInfoUpdated'=>[
            \app\listener\AdminInfoUpdated::class
        ]
    ],

    'subscribe' => [
    ],
];
