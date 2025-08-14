<?php
// 轮询检查任务
return [
    'scheduler_mode' => 'polling',
    'polling' => [
        'check_interval' => 10, // 10秒检查一次
    ],
];


// 切换到精确定时器模式
// return [
//     'scheduler_mode' => 'precise',
//     'precise' => [
//         'refresh_interval' => 60, // 60秒刷新一次任务列表
//         'max_tasks' => 1000,     // 最大任务数量
//         'hash_strategy' => 'database', // 哈希策略：redis, database, trigger
//     ],
// ];