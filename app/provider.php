<?php

use app\common\BaseRequest;
use app\ExceptionHandle;

// 容器Provider定义文件
return [
    'think\Request' => BaseRequest::class,
    'think\exception\Handle' => ExceptionHandle::class,
];
