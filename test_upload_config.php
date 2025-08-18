<?php
/**
 * 文件上传配置测试脚本
 * 使用方法：php test_upload_config.php
 */

// 引入ThinkPHP框架
require_once __DIR__ . '/vendor/autoload.php';

// 启动应用
$app = new \think\App();
$app->initialize();

try {
    echo "=== 文件上传配置测试 ===\n\n";
    
    // 测试系统配置
    echo "1. 测试系统配置...\n";
    
    // 获取当前存储方式
    $currentStorage = \app\model\SystemConfig::getCacheValue('upload_storage_type', 'local');
    echo "   当前存储方式: {$currentStorage}\n";
    
    // 获取存储类型列表
    $storageTypes = [
        ['key' => 'local', 'value' => '本地存储'],
        ['key' => 'qiniu', 'value' => '七牛云'],
        ['key' => 'aliyun_oss', 'value' => '阿里云OSS'],
        ['key' => 'qcloud_cos', 'value' => '腾讯云COS'],
        ['key' => 'aws_s3', 'value' => 'AWS S3']
    ];
    echo "   支持的存储类型: " . implode(', ', array_column($storageTypes, 'value')) . "\n";
    
    // 获取本地存储配置
    $localConfig = \app\model\SystemConfig::getCacheValue('upload_local_config', '{}');
    if (is_string($localConfig) && json_validate($localConfig)) {
        $localConfig = json_decode($localConfig, true);
    }
    echo "   本地存储配置: " . json_encode($localConfig, JSON_UNESCAPED_UNICODE) . "\n";
    
    // 获取通用配置
    $commonConfig = \app\model\SystemConfig::getCacheValue('upload_common_config', '{}');
    if (is_string($commonConfig) && json_validate($commonConfig)) {
        $commonConfig = json_decode($commonConfig, true);
    }
    echo "   通用配置: " . json_encode($commonConfig, JSON_UNESCAPED_UNICODE) . "\n";
    
    // 检查配置完整性
    echo "\n2. 检查配置完整性...\n";
    $configKeys = [
        'upload_storage_type',
        'upload_local_config',
        'upload_qiniu_config',
        'upload_aliyun_oss_config',
        'upload_qcloud_cos_config',
        'upload_aws_s3_config',
        'upload_common_config'
    ];
    
    foreach ($configKeys as $configKey) {
        $value = \app\model\SystemConfig::getCacheValue($configKey);
        if ($value) {
            echo "   {$configKey}: 已配置\n";
        } else {
            echo "   {$configKey}: 未配置\n";
        }
    }
    
    // 测试配置获取
    echo "\n3. 测试配置获取...\n";
    foreach ($configKeys as $configKey) {
        $value = \app\model\SystemConfig::getCacheValue($configKey);
        if (is_string($value) && json_validate($value)) {
            $value = json_decode($value, true);
        }
        echo "   {$configKey}: " . (is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value) . "\n";
    }
    
    // 测试File模型常量
    echo "\n4. 测试File模型常量...\n";
    $fileModel = new \app\model\File();
    echo "   本地存储常量: " . $fileModel::STORAGE_LOCAL . "\n";
    echo "   阿里云OSS常量: " . $fileModel::STORAGE_ALIYUN_OSS . "\n";
    echo "   腾讯云COS常量: " . $fileModel::STORAGE_QCLOUD_COS . "\n";
    echo "   AWS S3常量: " . $fileModel::STORAGE_AWS_S3 . "\n";
    
    echo "\n=== 测试完成 ===\n";
    echo "如果看到所有配置项都正常显示，说明配置功能正常。\n";
    echo "如果有错误，请检查：\n";
    echo "1. 是否已执行 upload_config_setup.sql 脚本\n";
    echo "2. 数据库连接是否正常\n";
    echo "3. 配置缓存是否已刷新\n";
    echo "4. File模型是否正确加载\n";
    
} catch (\Exception $e) {
    echo "测试失败: " . $e->getMessage() . "\n";
    echo "错误位置: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "错误堆栈:\n" . $e->getTraceAsString() . "\n";
}
