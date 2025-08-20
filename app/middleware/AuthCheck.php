<?php
namespace app\middleware;

use app\common\enum\Code;
use app\common\enum\Status;
use app\common\service\JwtService;
use app\model\Admin;

class AuthCheck
{
    /**
     * @throws \Exception
     */
    public function handle($request, \Closure $next)
    {
        $adminId=null;
//        if (!empty(Session::get("admin_id"))){
//            $adminId = Session::get("admin_id");
//        }
//        if (empty($adminId)&&!empty(Cookie::get("admin_id"))){
//            $adminId = Cookie::get("admin_id");
//        }

        if (empty($adminId)){

            // 从header获取Token
            $token = JwtService::getTokenFromHeader($request);


            if (!$token) {
                return $this->unauthorized('请登录');
            }
            // 验证管理员Token
            $payload = JwtService::verifyAdminToken($token);

            $adminId=$payload['admin_id']?:null;
        }

        if (empty($adminId)) {
            return $this->unauthorized('请登录');
        }

        try {



            $admin=Admin::getInfoFromCache($adminId);

            if ($admin->status!=Status::ENABLED->value){
                return $this->unauthorized("账号不可用");
            }

            // 将管理员信息存入请求上下文
            $request->adminId =$adminId;
            $request->admin=$admin;

            return $next($request);

        } catch (\Exception $e) {
            (new Admin())->reportError($e->getMessage(),(array)$e,$e->getCode());

            return $this->unauthorized($e->getMessage(), $e->getCode());
        }
    }



    private function unauthorized(string $message, int $code = Code::TOKEN_INVALID->value): \think\response\Json|\think\response\Redirect
    {
            return json([
                'code' => $code,
                'msg' => $message
            ]);

    }
}