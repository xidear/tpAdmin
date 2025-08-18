<?php

namespace app\common\service;

use think\facade\Config;

/**
 * 文件URL迁移服务
 * 专注于文件URL的域名迁移，支持所有类型的文件（图片、文档、视频等）
 * 不限制存储类型（本地、云存储等）
 */
class ImageUrlService
{
    /**
     * 本地存储识别规则
     * 本地存储：文件存储在本服务器的文件系统中，而不是云存储
     * 通过路径特征识别，不依赖域名或协议
     */
    private static array $localPatterns = [
        // 包含 /storage 路径的URL（ThinkPHP默认存储目录）
        '/\/storage/',
        // 包含 /uploads 路径的URL（常见上传目录）
        '/\/uploads/',
        // 包含 /public 路径的URL（公共资源目录）
        '/\/public/',
        // 包含 /app 路径的URL（应用目录）
        '/\/app/',
        // 相对路径（不以协议开头）
        '/^(?!https?:\/\/|ftp:\/\/|sftp:\/\/).+/'
    ];

    /**
     * 检查是否为本地存储URL
     * @param string $url
     * @return bool
     */
    public static function isLocalStorage(string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        // 如果包含localhost、127.0.0.1或内网IP，认为是本地存储
        if (str_contains($url, 'localhost') || str_contains($url, '127.0.0.1') || 
            preg_match('/192\.168\.\d+\.\d+/', $url) || preg_match('/10\.\d+\.\d+\.\d+/', $url)) {
            return true;
        }

        // 检查路径特征
        foreach (self::$localPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取图片完整URL
     * @param string $path 文件路径
     * @param string $domain 域名
     * @return string
     */
    public static function getImageUrl(string $path, string $domain = ''): string
    {
        if (empty($path)) {
            return '';
        }
        
        // 如果已经是完整URL，直接返回
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }
        
        // 如果没有提供域名，使用默认配置
        if (empty($domain)) {
            $domain = Config::get('filesystem.default_image_domain', '');
        }
        
        // 确保路径不以 / 开头
        $path = ltrim($path, '/');
        
        // 确保域名以 / 结尾
        $domain = rtrim($domain, '/') . '/';
        
        return $domain . $path;
    }

    /**
     * 生成新的图片URL
     * @param string $oldUrl
     * @param string $oldDomain
     * @param string $newDomain
     * @return string
     */
    public static function generateNewUrl(string $oldUrl, string $oldDomain, string $newDomain): string
    {
        if (empty($oldUrl) || empty($oldDomain) || empty($newDomain)) {
            return $oldUrl;
        }
        
        // 确保域名被正确trim
        $oldDomain = trim($oldDomain);
        $newDomain = trim($newDomain);
        
        // 去除域名末尾的斜杠
        $oldDomain = rtrim($oldDomain, '/');
        $newDomain = rtrim($newDomain, '/');
        
        return str_replace($oldDomain, $newDomain, $oldUrl);
    }

    /**
     * 检查URL是否需要迁移
     * @param string $url
     * @param string $oldDomain
     * @return bool
     */
    public static function needsMigration(string $url, string $oldDomain): bool
    {
        if (empty($url) || empty($oldDomain)) {
            return false;
        }
        
        // 检查是否为有效URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // 确保域名被正确trim
        $oldDomain = trim($oldDomain);
        $oldDomain = rtrim($oldDomain, '/');
        
        return str_contains($url, $oldDomain);
    }

    /**
     * 获取本地图片域名配置
     * @return string
     */
    public static function getLocalImageDomain(): string
    {
        return Config::get('filesystem.default_image_domain', '');
    }

    /**
     * 更新本地图片域名配置
     * @param string $newDomain
     * @return bool
     */
    public static function updateLocalImageDomain(string $newDomain): bool
    {
        // 由于域名配置在 system_config 表中，这里不需要更新配置文件
        // 可以通过更新 system_config 表中的 site_url 来实现
        return true;
    }
}
