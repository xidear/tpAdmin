<?php

use app\middleware\AuthCheck;
use app\middleware\AutoPermissionCheck;
use think\facade\Route;
// 后台路由组
Route::group('adminapi', function () {
    //    不需要登录
    Route::group(function () {
        Route::get('test', 'Index/index')->name("测试")->option(["description"=>"测试"]);
        // 获取登录所需数据 比如 是否需要验证码
        Route::get('login', 'Login/index')->name("获取登录数据")->option(["description"=>"获取验证码等登录数据"]);
        //登录表单
        Route::post('login', 'Login/doLogin')->name("登录")->option(["description"=>"登录"]);

    });

//    需要登录不需要权限验证
    Route::group(function () {
        Route::post('logout', 'Login/logout')->name("退出登录")->option(["description"=>"退出登录"]);
        // 首页
        Route::get('base', 'My/getBaseInfo')->name("获取基础信息")->option(["description"=>"获取头像昵称等基础数据"]);
        // 首页
        Route::get('dashboard', 'Index/dashboard')->name("数据看板")->option(["description"=>"获取数据看板"]);
        //修改密码
        Route::post('change_password', 'My/changePassword')->name("改密码")->option(["description"=>"修改个人密码"]);
//        获取菜单
        Route::get('get_menu', 'My/getMenu')->name("菜单")->option(["description"=>"获取左侧树状菜单"]);
        Route::get('get_buttons', 'My/getButtons')->name("权限按钮")->option(["description"=>"获取左侧树状菜单匹配的权限按钮"]);

        // 通用文件上传（支持传参指定存储类型和文件类型）
        Route::post('upload/file', 'File/upload')->name("上传文件")->option(["description"=>"上传doc zip 等文件"])
            ->allowCrossDomain()
            ->append([
                'storage_type' => 'local', // 默认本地存储
                'file_type' => 'all' ,      // 默认允许所有类型
                'uploader_type' => 'admin'       // 默认允许所有类型
            ]);

        // 图片专用上传（限制文件类型为图片，默认本地存储）
        Route::post('upload/image', 'File/upload')->name("上传图片")->option(["description"=>"上传 png jpg 等图片"])
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
            Route::get('index', 'index')->name("文件列表")->option(["description"=>"获取已有文件"]);
            Route::get('read/:admin_id', 'read')->name("读取文件")->option(["description"=>"显示某个文件"]);
        })->prefix("admin/File/");


//        管理员
        Route::group('role', function () {
            Route::get('index', 'index')->name("角色列表");
            Route::get('read/:role_id', 'read')->name("读取角色");
            Route::post('create', 'create')->name("创建角色");
            Route::put('update/:role_id', 'update')->name("更新角色");
            Route::delete('delete/:role_id', 'delete')->name("删除角色");
        })->prefix("admin/Role/");

//        管理员
        Route::group('admin', function () {
            Route::get('index', 'index')->name("管理员列表");
            Route::get('read/:admin_id', 'read')->name("读取管理员信息");
            Route::post('create', 'create')->name("创建管理员");
            Route::put('update/:admin_id', 'update')->name("更新管理员信息");
            Route::delete('delete/:admin_id', 'delete')->name("删除管理员");
            Route::delete('batch_delete', 'batchDelete')->name("批量删除管理员");
        })->prefix("admin/Admin/");

//        权限
        Route::group('permission', function () {
            Route::get('index', 'index')->name("权限列表");;
            Route::get('read/:permission_id', 'read')->name("权限详情");
            Route::post('sync', 'sync')->name("同步权限");
        })->prefix("admin/Permission/");

//        菜单
        Route::group('menu', function () {
            // 菜单管理
            Route::get('tree', 'tree')->name("菜单树");
            Route::get('index', 'index')->name("菜单列表");
            Route::get('read/:menu_id', 'read')->name("读取菜单");
            Route::post('create', 'create')->name("创建菜单");
            Route::put('update/:menu_id', 'update')->name("更新菜单");
            Route::delete('delete/:menu_id', 'delete')->name("删除菜单");
        })->prefix("admin/Menu/");

    })->middleware([
        AuthCheck::class,
        AutoPermissionCheck::class
    ]);
})->prefix('admin/')->allowCrossDomain();