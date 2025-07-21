<?php

use app\middleware\AuthCheck;
use app\middleware\AutoPermissionCheck;
use think\facade\Route;

// 后台路由组
Route::group('adminapi', function () {
    //    不需要登录
    Route::group(function () {
        // 获取登录所需数据 比如 是否需要验证码
        Route::get('login', 'Login/index');
        //登录表单
        Route::post('login', 'Login/doLogin');
        Route::post('logout', 'Login/logout');
    });

//    需要登录不需要权限验证
    Route::group(function () {
        // 首页
        Route::get('dashboard', 'Index/dashboard');
        //修改密码
        Route::get('change_password', 'My/changePassword');
//        获取菜单
        Route::get('get_menu', 'My/getMenu');
        Route::get('get_buttons', 'My/getButtons');

        // 通用文件上传（支持传参指定存储类型和文件类型）
        Route::post('upload/file', 'File/upload')
            ->allowCrossDomain()
            ->append([
                'storage_type' => 'local', // 默认本地存储
                'file_type' => 'all' ,      // 默认允许所有类型
                'uploader_type' => 'admin'       // 默认允许所有类型
            ]);

        // 图片专用上传（限制文件类型为图片，默认本地存储）
        Route::post('upload/image', 'File/upload')
            ->append([
                'storage_type' => 'local',
                'file_type' => 'image',
                'uploader_type' => 'admin'
            ]);


    })->middleware([AuthCheck::class]);
    // 需要登录同时需要权限验证
    Route::group(function () {


//        管理员
        Route::group('file', function () {
            Route::get('index', 'index');
            Route::get('read/:admin_id', 'read');
        })->prefix("admin/file/");


//        管理员
        Route::group('admin', function () {
            Route::get('index', 'index');
            Route::get('read/:admin_id', 'read');
            Route::post('create', 'create');
            Route::put('update/:admin_id', 'update');
            Route::delete('delete', 'delete');
        })->prefix("admin/admin/");

//        权限
        Route::group('permission', function () {
            Route::get('index', 'index');
            Route::get('read/:permission_id', 'read');
            Route::post('create', 'create');
            Route::put('update/:permission_id', 'update');
            Route::delete('delete', 'delete');
        })->prefix("admin/permission/");

//        菜单
        Route::group('menu', function () {
            // 菜单管理
            Route::get('tree', 'tree');
            Route::get('read/:menu_id', 'read');
            Route::post('create', 'create');
            Route::put('update/:menu_id', 'update');
            Route::delete('delete', 'delete');
        })->prefix("admin/menu/");
        // 角色管理
        Route::get('role', 'admin/Role/index');
        Route::post('role/:id/assign_menu', 'admin/Role/assignMenu');
    })->middleware([
        AuthCheck::class,
        AutoPermissionCheck::class
    ]);
})->prefix('admin/')->allowCrossDomain();