<?php
/**
 * 测试存储类型选项生成
 * 使用方法：php test_storage_options.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use app\controller\admin\ConfigForm;
use think\App;

// 模拟请求
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';

// 创建应用实例
$app = new App();

// 创建控制器实例
$controller = new ConfigForm($app);

// 获取表单数据
$response = $controller->getForm();

// 解析响应
$data = json_decode($response->getContent(), true);

if ($data['code'] === 200) {
    echo "✅ 配置表单获取成功\n\n";
    
    foreach ($data['data'] as $group) {
        echo "📁 配置分组: {$group['group_name']}\n";
        
        foreach ($group['fields'] as $field) {
            echo "  └─ {$field['label']} ({$field['key']}) - 类型: {$field['type']}\n";
            
            if (!empty($field['options'])) {
                echo "     选项:\n";
                foreach ($field['options'] as $option) {
                    echo "       • {$option['key']} => {$option['value']}\n";
                }
            }
            
            if ($field['key'] === 'upload_storage_type') {
                echo "     当前值: {$field['value']}\n";
            }
        }
        echo "\n";
    }
} else {
    echo "❌ 配置表单获取失败: {$data['msg']}\n";
}
