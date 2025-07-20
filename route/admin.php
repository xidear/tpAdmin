<?php

use app\middleware\AuthCheck;
use app\middleware\AutoPermissionCheck;
use think\facade\Route;

// 后台路由组
Route::group('adminapi', function () {
    // 获取登录所需数据 比如 是否需要验证码
    Route::get('login', 'Login/index');
    //登录表单
    Route::post('login', 'Login/doLogin');
    Route::post('logout', 'Login/logout');
//    需要登录不需要权限验证
    Route::group(function () {
        // 首页
        Route::get('dashboard', 'Index/dashboard');
        //修改密码
        Route::get('change_password', 'My/changePassword');
//        获取菜单
        Route::get('get_menu', 'My/getMenu');
        Route::get('get_buttons', 'My/getButtons');
    })->middleware([AuthCheck::class]);
    // 需要登录同时需要权限验证
    Route::group(function () {


//        权限
        Route::group('permission',function () {
            Route::get('index', 'index');
            Route::get('read/:id','read');
            Route::post('create', 'create');
            Route::put('update/:id', 'update');
            Route::delete('delete', 'delete');
        })->prefix("admin/permission/");

//        菜单
        Route::group('menu',function () {
            // 菜单管理
            Route::get('tree', 'tree');
            Route::get('read/:id','read');
            Route::post('create', 'create');
            Route::put('update/:id', 'update');
            Route::delete('delete', 'delete');
        })->prefix("admin/menu/");
        // 角色管理
        Route::get('role', 'admin/Role/index');
        Route::post('role/:id/assign_menu', 'admin/Role/assignMenu');
    })->middleware([
        AuthCheck::class,
        AutoPermissionCheck::class
    ]);
})->prefix('admin/');