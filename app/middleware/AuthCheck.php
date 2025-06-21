<?php
namespace app\middleware;

use app\common\enum\Code;
use app\common\enum\Status;
use app\model\Admin;
use app\model\AdminRole;
use app\model\Menu;
use app\service\JwtService;
use app\service\PermissionService;
use think\facade\View;

class AuthCheck
{
    public function handle($request, \Closure $next)
    {
        // 从header获取Token
        $token = $this->getTokenFromHeader($request);

        if (!$token) {
            return $this->unauthorized('缺少Token');
        }

        try {
            // 验证管理员Token
            $payload = JwtService::verifyAdminToken($token);


            $admin=(new Admin)->findOrFail($payload['admin_id']);

            if ($admin->status!=Status::Normal->value){
                return $this->unauthorized("用户已禁用");
            }

            // 将管理员信息存入请求上下文
            $request->adminId = $payload['admin_id'];
            $request->admin=$admin;

            return $next($request);

        } catch (\Exception $e) {
            (new Admin())->reportError($e->getMessage(),(array)$e,$e->getCode());

            return $this->unauthorized($e->getMessage(), $e->getCode());
        }
    }

    private function getTokenFromHeader($request): ?string
    {
        $authorization = $request->header('Authorization');

        if (!empty($authorization)) {
            if (!preg_match('/Bearer\s+(\S+)/i', $authorization, $matches)) {
                return null;
            }

            return $matches[1];
        }

        $token=request()->header('token')?:request()->post('token')?:request()->get('token')?:request()->param('token');

        return $token??null;
    }

    private function unauthorized(string $message, int $code = Code::TOKEN_INVALID->value): \think\response\Json|\think\response\Redirect
    {
            return json([
                'code' => $code,
                'msg' => $message
            ]);

    }
}