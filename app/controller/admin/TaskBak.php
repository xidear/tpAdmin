<?php


namespace app\controller\admin;

use app\common\BaseController;
use app\model\Task as TaskModel;
use app\model\TaskLog as TaskLogModel;
use app\request\admin\BatchDelete;
use app\request\admin\Delete;
use app\request\admin\Read;
use app\request\admin\task\Create;
use app\request\admin\task\Edit;
use think\facade\Request;
use think\Response;

class TaskBak extends BaseController
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
        if (Request::has('platform', 'get', true) && Request::get('platform') != 0) {
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
     * @param int $id
     * @param Read $read
     * @return Response
     */
    public function read(int $id, Read $read): Response
    {
        // 获取任务基本信息
        $task = (new TaskModel())->fetchOne($id);
        if ($task->isEmpty()) {
            return $this->error("未找到指定任务");
        }

        // 获取任务执行日志
        $logConditions = [
            ['task_id', '=', $id]
        ];



        $config=[];
        if ($read->has("page","get",true)){
            $config['pageNum']=$read->param('page');
        }else{
            $config['pageNum']=1;
        }
        if ($read->has("list_rows","get",true)){
            $config['pageSize']=$read->param('list_rows');
        }else{
            $config['pageSize']=15;
        }
        $logs = (new TaskLogModel())->fetchData(
            $logConditions,$config
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
        $params = $create->param();
        // 补充创建人信息
        $params['created_by'] = $this->adminId;
        $params['updated_by'] = $this->adminId;

        $task = (new TaskModel())->fetchOneOrCreate($params);

        // 清除任务缓存
   

        return $this->success($task, "任务创建成功");
    }

    /**
     * 编辑任务
     * @param int $id
     * @param Edit $edit
     * @return Response
     */
    public function update(int $id, Edit $edit): Response
    {
        $params = Request::param();
        $task = (new TaskModel())->fetchOne($id);

        if ($task->isEmpty()) {
            return $this->error("未找到指定任务");
        }

        // 补充更新人信息
        $params['updated_by'] = $this->adminId;

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
        $ids = $delete->delete("ids/a");

        $model = new TaskModel();
        if ($model->batchDeleteWithRelation($ids, ["logs"])) {
            // 清除缓存
            return $this->success("批量删除成功");
        } else {
            return $this->error($model->getMessage() ?: "批量删除失败");
        }
    }

    /**
     * 删除单个任务
     * @param int $id
     * @param Delete $delete
     * @return Response
     */
    public function delete(int $id, Delete $delete): Response
    {
        $model = new TaskModel();
        $task = $model->fetchOne($id);

        if ($task->isEmpty()) {
            return $this->error("未找到指定任务");
        }

        if ($model->batchDeleteWithRelation([$id], ["logs"])) {
            return $this->success("任务删除成功");
        } else {
            return $this->error($model->getMessage() ?: "任务删除失败");
        }
    }

    /**
     * 切换任务状态（开启/停止）
     * @param int $id
     * @return Response
     */
    public function toggleStatus(int $id): Response
    {
        $task = (new TaskModel())->fetchOne($id);

        if ($task->isEmpty()) {
            return $this->error("未找到指定任务");
        }

        $newStatus = $task->status == TaskModel::STATUS_ENABLED
            ? TaskModel::STATUS_DISABLED
            : TaskModel::STATUS_ENABLED;

        $task->status = $newStatus;
        $task->updated_by = $this->adminId;

        if ($task->save()) {
            // 清除缓存
        
            return $this->success([
                'status' => $newStatus
            ], $newStatus == TaskModel::STATUS_ENABLED ? "任务已开启" : "任务已停止");
        }

        return $this->error("状态切换失败");
    }

    /**
     * 立即执行一次任务
     * @param int $id
     * @return Response
     */
    public function executeNow(int $id): Response
    {
        $task = (new TaskModel())->fetchOne($id);

        if ($task->isEmpty()) {
            return $this->error("未找到指定任务");
        }

        // 检查任务是否已禁用
        if ($task->status == TaskModel::STATUS_DISABLED) {
            return $this->error("任务已禁用，无法执行");
        }

        // 调用任务执行服务
        $result = app()->make(\app\service\TaskService::class)->executeTask($task);

        if ($result['success']) {
            return $this->success($result['data'], "任务已触发执行");
        } else {
            return $this->error($result['message']);
        }
    }

    /**
     * 获取任务类型选项
     * @return Response
     */
    public function getTypeOptions(): Response
    {
        return $this->success(TaskModel::getTypeOptions());
    }

    /**
     * 获取平台选项
     * @return Response
     */
    public function getPlatformOptions(): Response
    {
        return $this->success(TaskModel::getPlatformOptions());
    }
}
