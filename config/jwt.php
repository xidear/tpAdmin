<?php
return [
    // Admin端配置
    'admin' => [
        'secret' => env('ADMIN_SECRET', 'your_strong_admin_secret'),
        'expire' => 3600 * 24 * 7, // 7天有效期
    ],

    // 预留的小程序配置
    'weapp' => [
        'secret' => env('WEAPP_SECRET', 'your_weapp_secret'),
        'expire' => 3600 * 24 * 30, // 30天有效期
    ]
];