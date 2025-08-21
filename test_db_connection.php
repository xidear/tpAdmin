<?php
/**
 * 测试数据库连接的简单脚本
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== 数据库连接测试 ===\n\n";

// 尝试连接数据库
try {
    $host = 'localhost';
    $dbname = 'tp_admin';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ 数据库连接成功！\n\n";
    
    // 测试查询图片分类表
    echo "测试查询图片分类表...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM image_category");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "图片分类表记录数: " . $result['count'] . "\n\n";
    
    // 测试查询文件表
    echo "测试查询文件表...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM file WHERE mime_type LIKE 'image/%'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "图片文件记录数: " . $result['count'] . "\n\n";
    
    // 测试查询图片标签表
    echo "测试查询图片标签表...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM image_tag");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "图片标签记录数: " . $result['count'] . "\n\n";
    
} catch (PDOException $e) {
    echo "✗ 数据库连接失败: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ 其他错误: " . $e->getMessage() . "\n";
}

echo "=== 测试完成 ===\n";
