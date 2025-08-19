<?php
namespace app\middleware;

use app\common\enum\task\Code;
use app\common\enum\task\Status;
use app\common\enum\task\YesOrNo;
use app\model\SystemLog;
use think\facade\Config;
use think\Response;

class LogRecord
{
    /**
     * 排除不需要记录日志的路由
     * @var array
     */
    protected $excludeRoutes = [
        'adminapi/test', // 测试路由
    ];

    /**
     * 处理请求
     * @param $request
     * @param \Closure $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        // 记录开始时间
        $startTime = microtime(true);

        // 执行请求
        $response = $next($request);

        // 计算执行时间
        $executionTime = microtime(true) - $startTime;

        // 记录日志
        $this->recordLog($request, $response, $executionTime);

        return $response;
    }

    /**
     * 记录日志
     * @param $request
     * @param $response
     * @param $executionTime
     */
    protected function recordLog($request, $response, $executionTime)
    {
        // 获取当前路由规则
        $rule = $request->rule();
        if (!$rule) {
            return;
        }


        // 路由名称
        $routeName = $rule->getName();
        $routePath = $rule->getRule();
        // 检查是否需要排除
        if (in_array($routePath, $this->excludeRoutes)) {
            return;
        }
        $fullController = request()->controller();
        $fullControllerArray = explode('.', $fullController);
        $module = array_shift($fullControllerArray);
        $controller = implode('.', $fullControllerArray);
        $action = $request->action();


        // 获取管理员信息
        $adminId = $request->adminId ?? 0;
        $username = '未知';
        if (!empty($request->admin)) {
            $username = $request->admin->username ?? '未知';
        } elseif ($routePath == 'adminapi/login') {
            $username = $request->param('username', '未知');
        }

        // 构建日志数据
        $logData = [
            'admin_id' => $adminId,
            'username' => $username,
            'module' => $module,
            'controller' => $controller, // 保存完整的控制器层级
            'action' => $action,
            'route_name' => $routeName,
            'route_path' => $routePath,
            'description' => $rule->getOption('description') ?? '',
            'request_method' => $request->method(),
            'request_url' => $request->url(true),
            'ip' => $request->ip(),
            'user_agent' => $request->server('HTTP_USER_AGENT') ?: '',
            'execution_time' => round($executionTime, 3),
        ];

        // 处理请求参数（敏感信息过滤）
        $params = $this->filterSensitiveData($request->param());
        $logData['request_param'] = !empty($params) ? json_encode($params, JSON_UNESCAPED_UNICODE) : '';

        // 处理响应状态
        $responseContent = $response->getContent();
        $responseData = json_decode($responseContent, true);
        if (is_array($responseData) && isset($responseData['code'])) {
            $logData['status'] = $responseData['code'] ==Code::SUCCESS->value ? YesOrNo::Yes: YesOrNo::No;
            $msg='';
            if (!empty($responseData['msg'])) {
               $msg= $responseData['msg'];
            }else{
                if (!empty($responseData['message'])) {
                    $msg = $responseData['message'];
                }
            }
            $logData['error_msg'] =$msg;
        } else {
            if (!empty($responseData)) {

                debug($responseData);
                $logData['status'] = $response->getCode() ==Code::SUCCESS->value ? YesOrNo::Yes: YesOrNo::No;
                $logData['error_msg'] = $response?->getReasonPhrase() ?? '';
            }

        }

        // 记录日志
        if (Config::get('app.log_async', true)) {
            \think\facade\Queue::push(\app\job\LogRecordJob::class, $logData);
        } else {
            SystemLog::create($logData);
        }
    }



    /**
     * 过滤敏感数据
     * @param array $data
     * @return array
     */
    protected function filterSensitiveData(array $data)
    {
        $sensitiveKeys = ['password', 'pwd', 'secret', 'token', 'card', 'mobile', 'phone'];

        foreach ($data as $key => &$value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $value = '***';
            } elseif (is_array($value)) {
                $value = $this->filterSensitiveData($value);
            }
        }

        return $data;
    }
}
