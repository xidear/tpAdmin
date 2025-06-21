<?php
namespace app\controller\admin;
use app\common\BaseController;
use app\common\enum\AdminType;
use app\model\Admin;
use app\request\admin\login\doLogin;
use app\service\JwtService;
use app\common\enum\Status;
use think\Response;

class Login extends BaseController{


    /**
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->success([
            'need_captcha'=>Status::Disabled->value
        ]);
    }

    /**
     * 登录
     * @param doLogin $request
     * @return Response
     */
    public function doLogin(doLogin $request): \think\Response
    {
        $data = $request->post();

        // 验证账号密码
        $admin = (new \app\model\Admin)->where('username', $data['username'])
            ->findOrEmpty();

        if ($admin->isEmpty()) {
            return $this->error('账号错误');
        }
        if ( !password_verify($data['password'], $admin->password)){
            return $this->error('密码错误');

        }
        if ($admin->type!=AdminType::Admin->value) {
            return $this->error('账号类型错误');
        }
        // 生成管理员Token
        $token = JwtService::makeToken($admin->getKey());

        return $this->success([
            'token' => $token['token'],
            'expires_in' => $token['expires_in'],
            'admin' => $admin->hidden(['password'])
        ]);
    }
}