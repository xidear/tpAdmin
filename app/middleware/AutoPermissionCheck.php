<?php
namespace app\middleware;

use app\service\PermissionService;

class AutoPermissionCheck
{
    public function handle(\app\common\BaseRequest $request, \Closure $next)
    {
        if ($request?->admin?->isSuper()) {
            return $next($request);
        }
        // 1. 获取当前路由信息
        $controller = $request->controller();
        $action = $request->action();
        $method = $request->method();
        $nodeName = $controller . "/" . $action;
        // 4. 验证权限
        if (!(new PermissionService)->check($request?->adminId, $nodeName, $method)) {
            return $this->denyResponse($request);
        }

        return $next($request);

    }




    protected function denyResponse($request): \think\response\Json
    {
            return json(['code' => \app\common\enum\Code::FORBIDDEN, 'msg' => '无权操作']);

    }
}
