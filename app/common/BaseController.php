<?php
declare (strict_types=1);

namespace app\common;

use app\common\trait\BaseTrait;
use app\common\trait\ReturnTrait;
use think\Response;

/**
 * 增强的基础控制器
 * 继承官方BaseController并添加统一返回功能
 */
abstract class BaseController extends \app\BaseController
{

    use BaseTrait;
    use  ReturnTrait;

    /**
     * 统一的导出方法
     * @param BaseModel $model 模型实例
     * @param array $headers 导出表头配置
     * @param string $filename 文件名
     * @param string $type 文件类型
     * @param bool $forceQueue 强制使用队列
     * @return Response
     */
    protected function doExport(BaseModel $model, array $headers, string $filename = 'export', string $type = 'xlsx', bool $forceQueue = false): Response
    {
        try {
            $result = $model->export($headers, $filename, $type, $forceQueue);
            
            // 如果是队列导出，返回JSON响应
            if (is_array($result)) {
                return $this->success($result, $result['message'] ?? '导出任务已创建');
            }
            
            // 直接导出已经处理完毕（会exit），这里不会执行到
            return $this->success([], '导出成功');
        } catch (\Exception $e) {
            return $this->error('导出失败：' . $e->getMessage());
        }
    }
}