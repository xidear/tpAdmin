<?php
namespace app\controller\admin;
use app\common\BaseController;
use app\common\enum\Status;
use app\common\service\JwtService;
use app\request\admin\login\DoLogin;
use think\facade\Cookie;
use think\facade\Session;
use think\Response;

class Login extends BaseController{


    /**
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->success([
            'need_captcha'=>Status::DISABLED->value
        ]);
    }

    /**
     * 登录
     * @param DoLogin $request
     * @return Response
     */
    public function doLogin(DoLogin $request): \think\Response
    {
        $data = request()->post();

        // 验证账号密码
        $admin = (new \app\model\Admin)->where('username', $data['username'])
            ->findOrEmpty();

        if ($admin->isEmpty()) {
            return $this->error('账号错误');
        }



        if ( !password_verify($data['password'], $admin->getData("password"))){
            return $this->error('密码错误');
        }
        if ($admin->status!=Status::ENABLED->value) {
            return $this->error('账号不可用');
        }



        Cookie::set("admin_id",$admin->getKey());
        Session::set("admin_id",$admin->getKey());


        // 生成管理员Token
        $tokenArray = JwtService::makeToken($admin->getKey());

        return $this->success([
            'token' => $tokenArray['token'],
            'expires_in' => $tokenArray['expires_in'],
            'admin' => $admin->hidden(['password'])
        ]);
    }


    /**
     * 退出登录
     * @return Response
     */
    public function logout(): Response
    {
        $token = JwtService::getTokenFromHeader($this->request);
        $result = JwtService::logout($token);
        if ($result['success']) {
            Cookie::delete("admin_id");
            Session::delete("admin_id");
            return $this->success($result['message']);
        } else {
            return $this->error($result['message']);
        }
    }

}