<?php
/**
 * 测试图片API的简单脚本
 * 使用方法：php test_image_api.php
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 模拟ThinkPHP环境
define('APP_PATH', __DIR__ . '/app/');
define('ROOT_PATH', __DIR__ . '/');

// 自动加载类
spl_autoload_register(function ($class) {
    $file = str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

echo "=== 图片API测试脚本 ===\n\n";

// 测试图片分类API
echo "1. 测试图片分类API...\n";
try {
    // 模拟App对象
    $mockApp = new class {
        public $request;
        public function __construct() {
            $this->request = new class {
                public function param($name, $default = null) { return $default; }
                public function has($name, $type = 'param', $default = false) { return false; }
                public function get($name, $default = null) { return $default; }
                public function domain() { return 'http://localhost:8848'; }
            };
        }
    };
    
    $controller = new app\controller\admin\Image($mockApp);
    $response = $controller->categories();
    echo "   成功！响应数据：\n";
    echo "   " . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
} catch (Exception $e) {
    echo "   失败！错误信息：" . $e->getMessage() . "\n\n";
}

// 测试图片列表API
echo "2. 测试图片列表API...\n";
try {
    // 模拟App对象
    $mockApp = new class {
        public $request;
        public function __construct() {
            $this->request = new class {
                public function param($name, $default = null) { return $default; }
                public function has($name, $type = 'param', $default = false) { return false; }
                public function get($name, $default = null) { return $default; }
                public function domain() { return 'http://localhost:8848'; }
            };
        }
    };
    
    $controller = new app\controller\admin\Image($mockApp);
    $response = $controller->index();
    echo "   成功！响应数据：\n";
    echo "   " . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
} catch (Exception $e) {
    echo "   失败！错误信息：" . $e->getMessage() . "\n\n";
}

echo "=== 测试完成 ===\n";
