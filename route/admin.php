<?php

use app\middleware\AuthCheck;
use app\middleware\AutoPermissionCheck;
use app\middleware\LogRecord;
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

        Route::group('enum', function () {
            // 获取指定枚举数据（免权限）
            Route::post('read/:enum_code', 'getEnum')
                ->name('获取枚举列表')
                ->option(['description' => '传入枚举名称，返回枚举数组']);

            // 获取所有可用枚举名称（免权限）
            Route::get('index', 'index')
                ->name('获取枚举名称列表')
                ->option(['description' => '返回所有支持的枚举名称']);
        })->prefix('admin/Enum/');


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


        Route::group('config_form', function () {
//            配置操作
            Route::get('index', 'getForm')->name("获取配置表单")->option(["description"=>"获取所有配置分组及对应配置项，用于前端多Tab展示"]);
            Route::post('save', 'saveByGroup')->name("分组批量保存")->option(["description"=>"按分组批量保存配置项值（多Tab页提交）"]);
            Route::post('refresh_cache', 'refreshCache')->name("刷新配置缓存")->option(["description"=>"手动刷新系统配置缓存"]);


        })->prefix("admin/ConfigForm/");


// 系统配置路由组
        Route::group('config', function () {
//            配置项本身的维护
            Route::get('index', 'index')->name("配置列表")->option(["description"=>"获取配置项列表"]);
            Route::post('create', 'create')->name("新增配置项")->option(["description"=>"新增单个系统配置项（含指定分组）"]);
            Route::get('read/:config_id', 'read')->name("配置项详情")->option(["description"=>"获取单个配置项详情，用于编辑页回显"]);
            Route::put('update/:config_id', 'update')->name("更新配置项")->option(["description"=>"编辑配置项（支持修改分组/移动分组）"]);
            Route::delete('delete/:config_id', 'delete')->name("删除配置项")->option(["description"=>"删除指定系统配置项"]);
            Route::delete('batch_delete', 'batchDelete')->name("批量删除配置项")->option(["description"=>"批量删除选中的系统配置项"]);
        })->prefix("admin/Config/");


// 系统配置路由组
        Route::group('config_group', function () {
            Route::get('index', 'index')->name("配置分组列表")->option(["description"=>"获取配置分组列表"]);
            Route::post('create', 'create')->name("新增配置分组")->option(["description"=>"新增单个分组"]);
            Route::get('read/:group_id', 'read')->name("配置分组详情")->option(["description"=>"获取配置分组详情"]);
            Route::put('update/:group_id', 'update')->name("更新配置分组")->option(["description"=>"编辑配置分组"]);
            Route::delete('delete/:group_id', 'delete')->name("删除配置分组")->option(["description"=>"删除指定系统配置分组"]);
        })->prefix("admin/ConfigGroup/");



        // 定时任务路由组
        Route::group('task', function () {
            // 任务列表
            Route::get('index', 'index')->name("定时任务列表")->option(["description"=>"获取定时任务列表及分页数据"]);
            // 任务详情（含执行日志）
            Route::get('read/:id', 'read')->name("定时任务详情")->option(["description"=>"获取单个定时任务详情及执行日志"]);
            // 创建任务
            Route::post('create', 'create')->name("创建定时任务")->option(["description"=>"新增定时任务配置"]);
            // 更新任务
            Route::put('update/:id', 'update')->name("更新定时任务")->option(["description"=>"修改定时任务配置"]);
            // 删除单个任务
            Route::delete('delete/:id', 'delete')->name("删除定时任务")->option(["description"=>"删除指定定时任务"]);
            // 批量删除任务
            Route::delete('batch_delete', 'batchDelete')->name("批量删除定时任务")->option(["description"=>"批量删除选中的定时任务"]);
            // 切换任务状态（启用/禁用）
            Route::post('toggle_status/:id', 'toggleStatus')->name("切换任务状态")->option(["description"=>"切换定时任务的启用/禁用状态"]);
            // 立即执行任务
            Route::post('execute_now/:id', 'executeNow')->name("立即执行任务")->option(["description"=>"立即执行指定的定时任务"]);
            // 获取任务类型选项
            Route::get('get_type_options', 'getTypeOptions')->name("任务类型选项")->option(["description"=>"获取任务类型下拉选项数据"]);
            // 获取平台选项
            Route::get('get_platform_options', 'getPlatformOptions')->name("平台选项")->option(["description"=>"获取运行平台下拉选项数据"]);
        })->prefix("admin/Task/");


//        日志
        Route::group('log', function () {
            Route::get('index', 'index')->name("日志列表")->option(["description"=>"日志列表"]);
            Route::get('read/:id', 'read')->name("读取日志信息")->option(["description"=>"显示单个日志的详情"]);
            Route::delete('delete/:id', 'delete')->name("删除日志")->option(["description"=>"删除日志"]);
            Route::delete('batch_delete', 'batchDelete')->name("批量删除日志")->option(["description"=>"批量删除日志"]);
        })->prefix("admin/SystemLog/");




//        文件
        Route::group('file', function () {
            Route::get('index', 'index')->name("文件列表")->option(["description"=>"获取已有文件"]);
            Route::get('read/:admin_id', 'read')->name("读取文件")->option(["description"=>"显示某个文件"]);
        })->prefix("admin/File/");


//        管理员角色
        Route::group('role', function () {
            Route::get('index', 'index')->name("角色列表")->option(["description"=>"角色列表"]);
            Route::get('read/:role_id', 'read')->name("读取角色")->option(["description"=>"获取角色详情"]);
            Route::post('create', 'create')->name("创建角色")->option(["description"=>"创建角色"]);
            Route::put('update/:role_id', 'update')->name("更新角色")->option(["description"=>"更新角色"]);
            Route::delete('delete/:role_id', 'delete')->name("删除角色")->option(["description"=>"删除角色"]);
        })->prefix("admin/Role/");

//        管理员
        Route::group('admin', function () {
            Route::get('index', 'index')->name("管理员列表")->option(["description"=>"管理员列表"]);
            Route::get('read/:admin_id', 'read')->name("读取管理员信息")->option(["description"=>"读取管理员信息"]);
            Route::post('create', 'create')->name("创建管理员")->option(["description"=>"创建管理员"]);
            Route::put('update/:admin_id', 'update')->name("更新管理员信息")->option(["description"=>"更新管理员信息"]);
            Route::delete('delete/:admin_id', 'delete')->name("删除管理员")->option(["description"=>"删除管理员"]);
            Route::delete('batch_delete', 'batchDelete')->name("批量删除管理员")->option(["description"=>"批量删除管理员"]);
        })->prefix("admin/Admin/");

//        权限
        Route::group('permission', function () {
            Route::get('index', 'index')->name("权限列表")->option(["description"=>"权限列表"]);;
            Route::get('read/:permission_id', 'read')->name("权限详情")->option(["description"=>"权限详情"]);
            Route::post('sync', 'sync')->name("同步权限")->option(["description"=>"同步权限"]);
        })->prefix("admin/Permission/");

//        菜单
        Route::group('menu', function () {
            // 菜单管理
            Route::get('tree', 'tree')->name("菜单树")->option(["description"=>"获取菜单树"]);
            Route::get('index', 'index')->name("菜单列表")->option(["description"=>"菜单列表"]);
            Route::get('read/:menu_id', 'read')->name("读取菜单")->option(["description"=>"读取菜单"]);
            Route::post('create', 'create')->name("创建菜单")->option(["description"=>"创建菜单"]);
            Route::put('update/:menu_id', 'update')->name("更新菜单")->option(["description"=>"更新菜单"]);
            Route::delete('delete/:menu_id', 'delete')->name("删除菜单")->option(["description"=>"删除菜单"]);
        })->prefix("admin/Menu/");

    })->middleware([
        AuthCheck::class,
        AutoPermissionCheck::class
    ]);
})->prefix('admin/')->allowCrossDomain()
    ->middleware(LogRecord::class); // 应用日志记录中间件