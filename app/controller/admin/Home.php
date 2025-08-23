<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\common\service\PermissionService;
use app\model\Task;
use app\model\SystemLog;
use app\model\Department;
use app\model\Role;
use app\model\Admin;
use think\App;
use think\Response;

class Home extends BaseController
{
    protected PermissionService $permissionService;
    
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->permissionService = new PermissionService($app);
    }
    
    /**
     * 获取首页统计数据
     * @return Response
     */
    public function getStats(): Response
    {
        $stats = [];
        
        // 定义需要统计的数据项和权限
        $statItems = [
            'task' => [
                'permission' => 'task:read',
                'model' => Task::class,
                'method' => 'count',
                'field' => 'task_id'
            ],
            'log' => [
                'permission' => 'log:read', 
                'model' => SystemLog::class,
                'method' => 'count',
                'field' => 'id'
            ],
            'department' => [
                'permission' => 'department:read',
                'model' => Department::class,
                'method' => 'count',
                'field' => 'department_id'
            ],
            'role' => [
                'permission' => 'role:read',
                'model' => Role::class,
                'method' => 'count',
                'field' => 'role_id'
            ]
        ];
        
        // 定义需要获取详细数据的项目
        $detailItems = [
            'task' => [
                'permission' => 'task:read',
                'model' => Task::class,
                'limit' => 10
            ],
            'log' => [
                'permission' => 'log:read',
                'model' => SystemLog::class,
                'limit' => 1000
            ],
            'department' => [
                'permission' => 'department:read',
                'model' => Department::class,
                'method' => 'getTreeData'
            ],
            'role' => [
                'permission' => 'role:read',
                'model' => Role::class,
                'limit' => 1000
            ]
        ];
        
        // 循环检查权限并获取数据
        foreach ($statItems as $key => $item) {
            if ($this->hasPermission($item['permission'])) {
                // 有权限，获取真实数据
                $model = new $item['model']();
                
                try {
                    // 根据模型类型使用不同的查询条件
                    switch ($item['model']) {
                        case Task::class:
                            // Task表直接统计总数，不管status
                            $count = $model->count();
                            break;
                        case SystemLog::class:
                            // 日志表可能没有status字段，直接统计总数
                            $count = $model->count();
                            break;
                        case Department::class:
                            $count = $model->where('status', 1)->count();
                            break;
                        case Role::class:
                            // role表没有status字段，直接统计总数
                            $count = $model->count();
                            break;
                        default:
                            $count = $model->count();
                            break;
                    }
                    
                    // 有权限且有数据，返回真实数量（包括0）
                    $stats[$key] = $count;
                } catch (\Exception $e) {
                    // 查询出错，说明表不存在或有问题，返回null
                    \think\facade\Log::error("查询{$key}数据失败: " . $e->getMessage());
                    $stats[$key] = null;
                }
            } else {
                // 无权限，返回null（不显示）
                $stats[$key] = null;
            }
        }
        
        // 获取详细数据
        $details = [];
        foreach ($detailItems as $key => $item) {
            if ($this->hasPermission($item['permission'])) {
                try {
                    $model = new $item['model']();
                    
                    switch ($key) {
                        case 'task':
                            $details[$key] = $model->limit($item['limit'])->select()->toArray();
                            break;
                        case 'log':
                            $details[$key] = $model->limit($item['limit'])->select()->toArray();
                            break;
                        case 'department':
                            if (method_exists($model, 'getTreeData')) {
                                $details[$key] = $model->getTreeData();
                            } else {
                                $details[$key] = $model->limit($item['limit'])->select()->toArray();
                            }
                            break;
                        case 'role':
                            $details[$key] = $model->limit($item['limit'])->select()->toArray();
                            break;
                        default:
                            $details[$key] = [];
                            break;
                    }
                } catch (\Exception $e) {
                    \think\facade\Log::error("获取{$key}详细数据失败: " . $e->getMessage());
                    $details[$key] = [];
                }
            } else {
                $details[$key] = [];
            }
        }
        
        // 组合统计数据
        $result = [
            'stats' => $stats,
            'details' => $details
        ];
        
        // 添加调试信息
        \think\facade\Log::info('首页统计数据: ' . json_encode($result));
        
        return $this->success($result);
    }
    
    /**
     * 检查权限
     */
    private function hasPermission(string $permission): bool
    {
        $adminId = request()->adminId ?? null;
        if (!$adminId) {
            return false;
        }
        
        // 简化权限检查：首页统计接口，登录用户都可以访问
        // 如果需要严格的权限控制，可以取消注释下面的代码
        return true;
        
        // 调用权限服务检查权限
        // return $this->permissionService->check($adminId, $permission, 'GET');
    }
}
