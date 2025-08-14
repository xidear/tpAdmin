<?php

namespace app\common\service;

use app\common\BaseService;
use app\model\AdminToken;
use Exception;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;
use think\facade\Log;

class JwtService extends BaseService
{
    const string TYPE_ADMIN = 'admin';
    const string TYPE_WEAPP = 'weapp'; // 预留字段
    const string CACHE_PREFIX = 'jwt_'; // 自定义 JWT 缓存前缀

    /**
     * 验证令牌（默认验证管理员令牌）
     * @throws Exception
     */
    public static function verifyAdminToken(string $token): array
    {
        return self::verifyToken($token, self::TYPE_ADMIN);
    }

    /**
     * 验证令牌（通用方法）
     * @throws Exception
     */
    private static function verifyToken(string $token, ?string $type = null): array
    {
        // 生成带自定义前缀的缓存键
        $cacheKey = self::CACHE_PREFIX . 'token_' . $token;
        $record = Cache::get($cacheKey);

        if (!$record) {
            // 缓存未命中，从数据库获取记录
            $record = (new \app\model\AdminToken)->where('token', $token)
                ->findOrEmpty();

            if (!$record->isEmpty()) {
                // 将记录存入缓存，设置缓存过期时间为令牌剩余有效期
                $remainingTime = $record['expire_time'] - time();
                if ($remainingTime > 0) {
                    Cache::set($cacheKey, $record, $remainingTime);
                }
            }
        }

        if ($record->isEmpty()) {
            throw new Exception('令牌无效', 40100);
        }

        // 获取配置
        $type = $type ?: $record['client_type'];
        $config = Config::get("jwt.{$type}");

        // 验证签名
        $payload = [
            'iat' => $record->created_at_int,
            'exp' => $record['expire_time'],
            'admin_id' => $record['admin_id'],
            'type' => $record['client_type'],
            'jti' => $record['jti'] // 使用数据库存储的jti
        ];
        // 使用相同的编码序列化
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $expectedToken = hash_hmac('sha256', $jsonPayload, $config['secret']);


        if (!hash_equals($expectedToken, $token)) {
            throw new \Exception('令牌已被篡改', 40101);
        }

        // 验证过期时间
        if (time() > $record['expire_time']) {
            // 令牌过期，删除缓存
            Cache::delete($cacheKey);
            throw new Exception('令牌已过期', 40102);
        }

        return $payload;
    }

    /**
     * 刷新令牌
     */
    public static function refreshToken(string $token): array
    {
        $payload = self::verifyToken($token);

        // 删除旧令牌缓存
        $cacheKey = self::CACHE_PREFIX . 'token_' . $token;
        Cache::delete($cacheKey);

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

    /**
     * 生成管理员令牌
     *
     * @param int $adminId 管理员ID
     * @param string $type 令牌类型 (默认为admin)
     * @return array [token, expire_time]
     */
    public static function makeToken(int $adminId, string $type = self::TYPE_ADMIN): array
    {
        $config = Config::get("jwt.{$type}");

        $payload = [
            'iat' => time(), // 签发时间
            'exp' => time() + $config['expire'], // 过期时间
            'admin_id' => $adminId,
            'type' => $type,
            'jti' => uniqid($type . '_', true) // 唯一ID
        ];

        Log::info("登录的时候用于生成token的负载:".serialize($payload));
        // 使用JSON_UNESCAPED_SLASHES和JSON_UNESCAPED_UNICODE确保编码一致
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $secret = $config['secret'];
        $token = hash_hmac('sha256', $jsonPayload, $secret);
        Log::info("登录的时候生成的token:".serialize($token));
        // 存入数据库
        $adminToken = new AdminToken();
        $adminToken->save([
            'admin_id' => $adminId,
            'client_type' => $type,
            'token' => $token,
            'expire_time' => $payload['exp'],
            'created_at' => $payload['iat'],
            'jti' => $payload['jti']
        ]);

//        $adminToken->append(["created_at_int"]);

        // 将新令牌记录存入缓存
        $cacheKey = self::CACHE_PREFIX . 'token_' . $token;
        $remainingTime = $payload['exp'] - time();
        if ($remainingTime > 0) {
            Cache::set($cacheKey, $adminToken, $remainingTime);
        }

        return [
            'token' => $token,
            'expires_in' => $config['expire'],
        ];
    }

    /**
     * 注销登录，将 token 拉黑
     *
     * @param string $token 要拉黑的 token
     * @return array 包含操作结果和消息的数组
     */
    public static function logout(string $token): array
    {
        try {
            // 验证令牌
            self::verifyToken($token);

            // 删除缓存
            $cacheKey = self::CACHE_PREFIX . 'token_' . $token;
            Cache::delete($cacheKey);


            // 将令牌拉黑
            $result = AdminToken::where('token', $token)
                ->delete();

            if ($result) {
                return [
                    'success' => true,
                    'message' => '注销成功，令牌已被拉黑'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '注销失败，无法拉黑令牌'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => '注销失败，' . $e->getMessage()
            ];
        }
    }


    public static function getTokenFromHeader($request): ?string
    {
        $authorization = $request->header('Authorization');
        if (!empty($authorization)) {
            if (!preg_match('/Bearer\s+(\S+)/i', $authorization, $matches)) {
                return null;
            }
            return $matches[1];
        }
        $token=  $request->header('x-access-token')?:request()->header('token')?:request()->post('token')?:request()->get('token')?:request()->param('token');
        return $token??null;
    }


}