<?php

namespace app\middleware;

use app\common\BaseRequest;
use app\common\enum\Code;
use app\service\PermissionService;
use Closure;
use think\response\Json;

class AutoPermissionCheck
{


    public function handle(BaseRequest $request, Closure $next)
    {
        // 超级管理员直接放行
        if ($request?->admin?->isSuper()) {
            return $next($request);
        }

        $controller = $request->controller(false, true);
        $action = $request->action();
        $nodeName = $controller . "/" . $action;
        $method = strtoupper($request->method());
        $permissionService = new PermissionService();
        if (!$permissionService->check($request?->adminId, $nodeName, $method)) {
            return $this->denyResponse($permissionService->getMessage());
        }

        return $next($request);
    }


    protected function denyResponse(string|array $msg = '无权操作'): Json
    {
        return json(['code' => Code::FORBIDDEN, 'msg' => $msg]);

    }
}
