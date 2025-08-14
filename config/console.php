<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
use app\command\GenerateRegionCache;
use app\command\TestCommand;
return [
    // 指令定义
    'commands' => [
        'region:cache'=> GenerateRegionCache::class,
         'test:command' =>TestCommand::class,
    ],
];
