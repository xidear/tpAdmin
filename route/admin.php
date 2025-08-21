<?php

use app\middleware\AuthCheck;
use app\middleware\AutoPermissionCheck;
use app\middleware\LogRecord;
use think\facade\Route;
// 后台路由组
Route::get('api/test', 'Test/index');

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
            Route::get('read/:enum_code', 'getEnum')
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

        // 文件上传（支持传参指定文件类型和权限）
        Route::post('upload/file', 'File/upload')->name("上传文件")->option(["description"=>"上传文件，支持图片、视频、文档等类型"])
            ->allowCrossDomain();

        // 图片专用上传（限制文件类型为图片）
        Route::post('upload/image', 'File/upload')->name("上传图片")->option(["description"=>"上传图片文件，自动限制为图片类型"])
            ->allowCrossDomain();

        // 视频专用上传（限制文件类型为视频）
        Route::post('upload/video', 'File/upload')->name("上传视频")->option(["description"=>"上传视频文件，自动限制为视频类型"])
            ->allowCrossDomain();

        // 通用文件选择器基础功能 - 登录即可使用（不需要特殊权限）
        Route::group('file', function () {
            Route::get('index', 'index')->name("文件列表")->option(["description"=>"获取文件列表，支持类型筛选"]);
            Route::get('read/:id', 'read')->name("文件详情")->option(["description"=>"获取文件详情"]);
            Route::get('categories', 'categories')->name("文件分类列表")->option(["description"=>"获取文件分类树形结构"]);
            Route::get('tags', 'tags')->name("文件标签列表")->option(["description"=>"获取文件标签列表"]);
        })->prefix("admin/File/");

    })->middleware([AuthCheck::class]);
    // 需要登录同时需要权限验证
    Route::group(function () {


//        省市区管理
        Route::group('region', function () {
            // 地区树形结构
            Route::get('tree', 'tree')->name("地区树")->option(["description"=>"获取省市区树形结构数据"]);
            // 地区列表
            Route::get('index', 'index')->name("地区列表")->option(["description"=>"获取地区列表及分页数据"]);
            // 地区详情
            Route::get('read/:region_id', 'read')->name("读取地区")->option(["description"=>"获取单个地区详情"]);
            // 新增地区
            Route::post('create', 'create')->name("创建地区")->option(["description"=>"新增省市区数据"]);
            // 更新地区
            Route::put('update/:region_id', 'update')->name("更新地区")->option(["description"=>"编辑地区信息（含改名、调级等）"]);
            // 删除地区（软删除）
            Route::delete('delete/:region_id', 'delete')->name("删除地区")->option(["description"=>"软删除指定地区"]);
            // 恢复地区
            Route::post('restore/:region_id', 'restore')->name("恢复地区")->option(["description"=>"恢复已删除的地区"]);
            Route::post('split', 'split')->name("拆分")->option(["description"=>"将地区拆分为多个子地区"]);
            
            Route::post('merge', 'merge')->name("合并")->option(["description"=>"合并地区"]);

            // 刷新缓存
            Route::get('refresh_cache', 'refreshCache')->name("清除缓存")
            ->option(["description"=>"清除地区缓存"]);
            // 获取子地区
            Route::get('children/:parent_id', 'children')->name("子地区列表")->option(["description"=>"获取指定地区的子地区列表"]);
        })->prefix("admin/Region/");



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
            Route::get('read/:system_config_id', 'read')->name("配置项详情")->option(["description"=>"获取单个配置项详情，用于编辑页回显"]);
            Route::put('update/:system_config_id', 'update')->name("更新配置项")->option(["description"=>"编辑配置项（支持修改分组/移动分组）"]);
            Route::delete('delete/:system_config_id', 'delete')->name("删除配置项")->option(["description"=>"删除指定系统配置项"]);
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
            Route::get('read/:task_id', 'read')->name("定时任务详情")->option(["description"=>"获取单个定时任务详情及执行日志"]);
            // 创建任务
            Route::post('create', 'create')->name("创建定时任务")->option(["description"=>"新增定时任务配置"]);
            // 更新任务
            Route::put('update/:task_id', 'update')->name("更新定时任务")->option(["description"=>"修改定时任务配置"]);
            // 删除单个任务
            Route::delete('delete/:task_id', 'delete')->name("删除定时任务")->option(["description"=>"删除指定定时任务"]);
            // 批量删除任务
            Route::delete('batch_delete', 'batchDelete')->name("批量删除定时任务")->option(["description"=>"批量删除选中的定时任务"]);
            // 切换任务状态（启用/禁用）
            Route::post('toggle_status/:task_id', 'toggleStatus')->name("切换任务状态")->option(["description"=>"切换定时任务的启用/禁用状态"]);
            // 立即执行任务
            Route::post('execute_now/:task_id', 'executeNow')->name("立即执行任务")->option(["description"=>"立即执行指定的定时任务"]);
            // 获取任务类型选项
            Route::get('get_type_options', 'getTypeOptions')->name("任务类型选项")->option(["description"=>"获取任务类型下拉选项数据"]);
            // 获取平台选项
            Route::get('get_platform_options', 'getPlatformOptions')->name("平台选项")->option(["description"=>"获取运行平台下拉选项数据"]);
        })->prefix("admin/Task/");


//        日志
        Route::group('log', function () {
            Route::get('index', 'index')->name("日志列表")->option(["description"=>"日志列表"]);
            Route::get('read/:task_log_id', 'read')->name("读取日志信息")->option(["description"=>"显示单个日志的详情"]);
            Route::delete('delete/:task_log_id', 'delete')->name("删除日志")->option(["description"=>"删除日志"]);
            Route::delete('batch_delete', 'batchDelete')->name("批量删除日志")->option(["description"=>"批量删除日志"]);
        })->prefix("admin/SystemLog/");




//        文件管理功能 - 需要file.manage权限
        Route::group('file', function () {
            // 文件管理页面相关（需要权限）
            Route::delete('delete/:file_id', 'delete')->name("删除文件")->option(["description"=>"删除指定文件", "permission"=>"file.manage"]);
            Route::delete('batch_delete', 'batchDelete')->name("批量删除文件")->option(["description"=>"批量删除文件", "permission"=>"file.manage"]);
            
            // 分类管理功能 - 需要 file.manage 权限
            Route::post('category/create', 'createCategory')->name("创建文件分类")->option(["description"=>"创建新的文件分类", "permission"=>"file.manage"]);
            Route::put('category/update/:id', 'updateCategory')->name("更新文件分类")->option(["description"=>"更新文件分类信息", "permission"=>"file.manage"]);
            Route::delete('category/delete/:id', 'deleteCategory')->name("删除文件分类")->option(["description"=>"删除文件分类", "permission"=>"file.manage"]);
            
            // 标签管理功能 - 需要 file.manage 权限
            Route::post('tag/create', 'createTag')->name("创建文件标签")->option(["description"=>"创建新的文件标签", "permission"=>"file.manage"]);
            Route::delete('tag/delete/:id', 'deleteTag')->name("删除文件标签")->option(["description"=>"删除文件标签", "permission"=>"file.manage"]);
            
            // 文件操作功能 - 需要 file.manage 权限
            Route::post('move-category', 'moveCategory')->name("移动文件分类")->option(["description"=>"批量移动文件到其他分类", "permission"=>"file.manage"]);
            
            // 图片迁移相关 - 需要 file.manage 权限
            Route::get('get-migration-preview', 'getMigrationPreview')->name("获取迁移预览")->option(["description"=>"获取图片URL迁移预览", "permission"=>"file.manage"]);
            Route::post('migrate-urls', 'migrateUrls')->name("执行迁移")->option(["description"=>"执行图片URL迁移", "permission"=>"file.manage"]);
        })->prefix("admin/File/");

//        部门管理
        Route::group('department', function () {
            // 部门列表（树状结构）
            Route::get('index', 'index')->name("部门列表")->option(["description"=>"获取部门树状结构数据"]);
            // 部门列表（平铺结构）
            Route::get('list', 'list')->name("部门平铺列表")->option(["description"=>"获取部门平铺列表，用于选择器"]);
            // 部门详情
            Route::get('read/:department_id', 'read')->name("部门详情")->option(["description"=>"获取单个部门详情"]);
            // 创建部门
            Route::post('create', 'create')->name("创建部门")->option(["description"=>"新增部门"]);
            // 更新部门
            Route::put('update/:department_id', 'update')->name("更新部门")->option(["description"=>"编辑部门信息"]);
            // 删除部门
            Route::delete('delete/:department_id', 'delete')->name("删除部门")->option(["description"=>"删除指定部门"]);
            // 批量删除部门
            Route::delete('batch-delete', 'batchDelete')->name("批量删除部门")->option(["description"=>"批量删除选中的部门"]);
            // 更新部门状态
            Route::put('update-status/:department_id', 'updateStatus')->name("更新部门状态")->option(["description"=>"启用或禁用部门"]);
            // 导出部门数据
            Route::get('export', 'export')->name("导出部门")->option(["description"=>"导出部门数据"]);
            
            // 部门职位相关
            Route::get('positions/:department_id', 'positions')->name("部门职位列表")->option(["description"=>"获取指定部门的职位列表"]);
            Route::post('position/create', 'createPosition')->name("创建职位")->option(["description"=>"新增部门职位"]);
            Route::put('position/update/:position_id', 'updatePosition')->name("更新职位")->option(["description"=>"编辑部门职位"]);
            Route::delete('position/delete/:position_id', 'deletePosition')->name("删除职位")->option(["description"=>"删除部门职位"]);
        })->prefix("admin/Department/");


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


