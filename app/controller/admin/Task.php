<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\common\enum\Status;
use app\common\enum\task\TaskPlatform;
use app\common\enum\task\TaskType;
use app\common\service\TaskService;
use app\model\Task as TaskModel;
use app\model\TaskLog as TaskLogModel;
use app\request\admin\BatchDelete;
use app\request\admin\task\Create;
use app\request\admin\Delete;
use app\request\admin\task\Edit;
use app\request\admin\Read;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Request;
use think\Response;

class Task extends BaseController
{
    /**
     * 任务列表
     * @return Response
     */
    public function index(): Response
    {
        $conditions = [];

        // 关键词搜索
        if (Request::has('keyword', 'get', true)) {
            $keyword = Request::get('keyword');
            $conditions[] = ['name|description|content', 'like', "%{$keyword}%"];
        }

        // 状态筛选
        if (Request::has('status', 'get', true)) {
            $conditions[] = ['status', '=', Request::get('status')];
        }

        // 平台筛选
        if (Request::has('platform', 'get', true) && Request::get('platform') != TaskPlatform::ALL->value) {
            $conditions[] = ['platform', '=', Request::get('platform')];
        }

        // 任务类型筛选
        if (Request::has('type', 'get', true) && Request::get('type') != 0) {
            $conditions[] = ['type', '=', Request::get('type')];
        }

        $list = (new TaskModel())->fetchData(
            $conditions
        );

        return $this->success($list);
    }

    /**
     * 任务详情（包含执行日志）
     * @param int $task_id
     * @param Read $read
     * @return Response
     */
    public function read(int $task_id, Read $read): Response
    {
        // 获取任务基本信息
        $task = (new TaskModel())->fetchOne($task_id);
        if ($task->isEmpty()) {
            return $this->error("未找到指定任务");
        }

        // 获取任务执行日志
        $logConditions = [
            ['task_id', '=', $task_id]
        ];

        $config = [];
        if (request()->has("page", "get", true)) {
            $config['pageNum'] = request()->param('page');
        } else {
            $config['pageNum'] = 1;
        }
        if (request()->has("list_rows", "get", true)) {
            $config['pageSize'] = request()->param('list_rows');
        } else {
            $config['pageSize'] = 15;
        }
        $logs = (new TaskLogModel())->fetchData(
            $logConditions, $config
        );

        return $this->success([
            'task' => $task,
            'logs' => $logs
        ]);
    }

    /**
     * 新增任务
     * @param Create $create
     * @return Response
     */
    public function create(Create $create): Response
    {
        $params = request()->param();
        // 补充创建人信息
        $params['created_by'] = request()->adminId;
        $params['updated_by'] = request()->adminId;

        $task = (new TaskModel())->fetchOneOrCreate($params);


        return $this->success($task, "任务创建成功");
    }

    /**
     * 编辑任务
     * @param int $task_id
     * @param Edit $edit
     * @return Response
     */
    public function update(int $task_id, Edit $edit): Response
    {
        $params = Request::param();
        $task = (new TaskModel())->fetchOne($task_id);

        if ($task->isEmpty()) {
            return $this->error("未找到指定任务");
        }

        // 补充更新人信息
        $params['updated_by'] = request()->adminId;

        if ($task->intelligentUpdate($params)) {
            return $this->success($task, "任务编辑成功");
        }

        return $this->error("任务编辑失败");
    }

    /**
     * 批量删除任务
     * @param BatchDelete $delete
     * @return Response
     */
    public function batchDelete(BatchDelete $delete): Response
    {
        $ids = request()->delete("ids/a");

        $model = new TaskModel();
        if ($model->batchDeleteWithRelation($ids, ["logs"])) {
            // 清除缓存
            TaskModel::clearCacheBatch($ids);
            return $this->success("批量删除成功");
        } else {
            return $this->error($model->getMessage() ?: "批量删除失败");
        }
    }

    /**
     * 删除单个任务
     * @param int $task_id
     * @param Delete $delete
     * @return Response
     */
    public function delete(int $task_id, Delete $delete): Response
    {
        $model = new TaskModel();
        $task = $model->fetchOne($task_id);

        if ($task->isEmpty()) {
            return $this->error("未找到指定任务");
        }

        if ($model->batchDeleteWithRelation([$task_id], ["logs"])) {
            // 清除缓存
            TaskModel::clearCache($task_id);
            return $this->success("任务删除成功");
        } else {
            return $this->error($model->getMessage() ?: "任务删除失败");
        }
    }

    /**
     * 切换任务状态（开启/停止）
     * @param int $task_id
     * @return Response
     */
    public function toggleStatus(int $task_id): Response
    {
        $task = (new TaskModel())->fetchOne($task_id);

        if ($task->isEmpty()) {
            return $this->error("未找到指定任务");
        }

        // 使用通用状态枚举切换状态
        $newStatus = $task->status == Status::ENABLED->value
            ? Status::DISABLED->value
            : Status::ENABLED->value;

        $task->status = $newStatus;
        $task->updated_by = request()->adminId;

        if ($task->save()) {
            // 清除缓存
            return $this->success([
                'status' => $newStatus
            ], $newStatus == Status::ENABLED->value ? "任务已开启" : "任务已停止");
        }

        return $this->error("状态切换失败");
    }

    /**
     * 立即执行一次任务
     * @param int $task_id
     * @return Response
     */
    public function executeNow(int $task_id): Response
    {
        $task = (new TaskModel())->fetchOne($task_id);

        if ($task->isEmpty()) {
            return $this->error("未找到指定任务");
        }

        // 检查任务是否已禁用（使用通用状态枚举）
        if ($task->status == Status::DISABLED->value) {
            return $this->error("任务已禁用，无法执行");
        }

            $service =app()->make(TaskService::class);
            $result = $service->executeTask($task);

        if ($result) {
            return $this->success($service->getReturnData, "任务已触发执行");
        } else {
            return $this->error($service->getMessage());
        }
    }

    /**
     * 获取任务类型选项
     * @return Response
     */
    public function getTypeOptions(): Response
    {
        // 返回任务类型枚举列表
        return $this->success(TaskType::getList());
    }

    /**
     * 获取平台选项
     * @return Response
     */
    public function getPlatformOptions(): Response
    {
        // 返回平台枚举列表
        return $this->success(TaskPlatform::getList());
    }
}