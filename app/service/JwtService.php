<?php

namespace app\service;

use think\facade\Config;
use think\facade\Db;

class JwtService
{
    const string TYPE_ADMIN = 'admin';
    const string TYPE_WEAPP = 'weapp'; // 预留字段

    /**
     * 生成管理员令牌
     *
     * @param int $adminId 管理员ID
     * @param string $type 令牌类型 (默认为admin)
     * @return array [token, expire_time]
     */
    public static function makeToken(int $adminId, string $type = self::TYPE_ADMIN): array
    {
        $config = Config::get('jwt');

        $payload = [
            'iat' => time(), // 签发时间
            'exp' => time() + $config['admin']['expire'], // 过期时间
            'admin_id' => $adminId,
            'type' => $type,
            'jti' => uniqid($type . '_', true) // 唯一ID
        ];

        // 使用admin专属密钥
        $secret = $config['admin']['secret'];
        $token = hash_hmac('sha256', json_encode($payload), $secret);

        // 存入数据库
        Db::name('admin_token')->insert([
            'admin_id' => $adminId,
            'client_type' => $type,
            'token' => $token,
            'expire_time' => $payload['exp'],
            'created_at' => $payload['iat']
        ]);

        return [
            'token' => $token,
            'expires_in' => $config['admin']['expire'],
        ];
    }

    /**
     * 验证令牌（默认验证管理员令牌）
     */
    public static function verifyAdminToken(string $token): array
    {
        return self::verifyToken($token, self::TYPE_ADMIN);
    }

    /**
     * 验证令牌（通用方法）
     * @throws \Exception
     */
    private static function verifyToken(string $token, ?string $type = null): array
    {
        // 1. 从数据库获取记录
        $record = Db::name('admin_token')
            ->where('token', $token)
            ->findOrEmpty();

        if ($record->isEmpty()) {
            throw new \Exception('令牌无效', 40100);
        }

        // 获取配置
        $type = $type ?: $record['client_type'];
        $config = Config::get("jwt.{$type}");

        // 验证签名
        $payload = [
            'iat' => $record['created_at'],
            'exp' => $record['expire_time'],
            'admin_id' => $record['admin_id'],
            'type' => $record['client_type'],
            'jti' => $record['jti'] // 使用数据库存储的jti
        ];

        $expectedToken = hash_hmac('sha256', json_encode($payload), $config['secret']);

        if (!hash_equals($expectedToken, $token)) {
            throw new \Exception('令牌已被篡改', 40101);
        }

        // 验证过期时间
        if (time() > $record['expire_time']) {
            throw new \Exception('令牌已过期', 40102);
        }

        return $payload;
    }

    /**
     * 刷新令牌
     */
    public static function refreshToken(string $token): array
    {
        $payload = self::verifyToken($token);

        // 删除旧令牌
        Db::name('admin_token')
            ->where('token', $token)
            ->delete();

        // 创建新令牌
        return self::makeToken(
            $payload['admin_id'],
            $payload['type']
        );
    }
}