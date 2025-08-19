<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\model\Department as DepartmentModel;
use app\model\DepartmentPosition as DepartmentPositionModel;
use app\model\DepartmentAdmin as DepartmentAdminModel;
use app\model\Admin as AdminModel;
use app\common\service\export\ExportService;
use app\request\admin\department\Create as CreateRequest;
use app\request\admin\department\Update as UpdateRequest;
use app\request\admin\department\BatchDelete as BatchDeleteRequest;
use app\request\admin\department\UpdateStatus as UpdateStatusRequest;
use app\request\admin\department\CreatePosition as CreatePositionRequest;
use app\request\admin\department\UpdatePosition as UpdatePositionRequest;
use think\Response;

class Department extends BaseController
{
    /**
     * 获取部门列表（树状结构）
     * @return Response
     */
    public function index(): Response
    {
        $conditions = $this->buildConditions();
        $list = DepartmentModel::getTreeData($conditions);
        return $this->success($list);
    }

    /**
     * 获取部门列表（平铺结构，用于选择器）
     * @return Response
     */
    public function list(): Response
    {
        $conditions = $this->buildConditions();
        $list = (new DepartmentModel())->fetchData($conditions);
        return $this->success($list);
    }

    /**
     * 获取部门详情
     * @param int $department_id
     * @return Response
     */
    public function read(int $department_id): Response
    {
        $department = DepartmentModel::with(['leader', 'positions'])->find($department_id);
        if (!$department) {
            return $this->error('部门不存在');
        }
        return $this->success($department);
    }

    /**
     * 创建部门
     * @param CreateRequest $request
     * @return Response
     */
    public function create(CreateRequest $request): Response
    {
        $data = $request->post();
        
        // 检查部门编码唯一性
        if (!empty($data['code'])) {
            $exists = DepartmentModel::where('code', $data['code'])->find();
            if ($exists) {
                return $this->error('部门编码已存在');
            }
        }

        // 检查父部门是否存在
        if (!empty($data['parent_id'])) {
            $parent = DepartmentModel::find($data['parent_id']);
            if (!$parent) {
                return $this->error('父部门不存在');
            }
        }

        $department = new DepartmentModel();
        $department->save($data);
        
        // 更新部门路径
        $department->updatePath();
        
        return $this->success($department, '创建成功');
    }

    /**
     * 更新部门
     * @param int $department_id
     * @param UpdateRequest $request
     * @return Response
     */
    public function update(int $department_id, UpdateRequest $request): Response
    {
        $department = DepartmentModel::find($department_id);
        if (!$department) {
            return $this->error('部门不存在');
        }

        $data = $request->put();
        
        // 检查部门编码唯一性
        if (!empty($data['code']) && $data['code'] !== $department->code) {
            $exists = DepartmentModel::where('code', $data['code'])->where('department_id', '<>', $department_id)->find();
            if ($exists) {
                return $this->error('部门编码已存在');
            }
        }

        // 检查父部门是否存在且不能是自己或自己的子部门
        if (!empty($data['parent_id'])) {
            if ($data['parent_id'] == $department_id) {
                return $this->error('不能选择自己作为父部门');
            }
            
            $childrenIds = $department->getAllChildrenIds();
            if (in_array($data['parent_id'], $childrenIds)) {
                return $this->error('不能选择子部门作为父部门');
            }
            
            $parent = DepartmentModel::find($data['parent_id']);
            if (!$parent) {
                return $this->error('父部门不存在');
            }
        }

        $oldParentId = $department->parent_id;
        $department->save($data);
        
        // 如果父部门发生变化，需要更新路径
        if ($oldParentId != $data['parent_id']) {
            $department->updatePath();
            $department->updateChildrenPath();
        }
        
        return $this->success($department, '更新成功');
    }

    /**
     * 删除部门
     * @param int $department_id
     * @return Response
     */
    public function delete(int $department_id): Response
    {
        $department = DepartmentModel::find($department_id);
        if (!$department) {
            return $this->error('部门不存在');
        }

        if (!$department->canDelete()) {
            return $this->error('该部门下有子部门或关联管理员，无法删除');
        }

        $department->delete();
        return $this->success([], '删除成功');
    }

    /**
     * 批量删除部门
     * @param BatchDeleteRequest $request
     * @return Response
     */
    public function batchDelete(BatchDeleteRequest $request): Response
    {
        $ids = $request->post('ids');
        
        $departments = DepartmentModel::whereIn('department_id', $ids)->select();
        $canDeleteIds = [];
        $cannotDeleteNames = [];

        foreach ($departments as $department) {
            if ($department->canDelete()) {
                $canDeleteIds[] = $department->department_id;
            } else {
                $cannotDeleteNames[] = $department->name;
            }
        }

        if (!empty($canDeleteIds)) {
            DepartmentModel::destroy($canDeleteIds);
        }

        if (!empty($cannotDeleteNames)) {
            return $this->error('以下部门无法删除：' . implode('、', $cannotDeleteNames));
        }

        return $this->success([], '删除成功');
    }

    /**
     * 更新部门状态
     * @param int $department_id
     * @param UpdateStatusRequest $request
     * @return Response
     */
    public function updateStatus(int $department_id, UpdateStatusRequest $request): Response
    {
        $department = DepartmentModel::find($department_id);
        if (!$department) {
            return $this->error('部门不存在');
        }

        $status = $request->put('status');
        $department->status = $status;
        $department->save();

        return $this->success([], '状态更新成功');
    }

    /**
     * 获取部门职位列表
     * @param int $departmentId
     * @return Response
     */
    public function positions(int $departmentId): Response
    {
        $positions = DepartmentPositionModel::where('department_id', $departmentId)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select();
        
        return $this->success($positions);
    }

    /**
     * 创建部门职位
     * @param CreatePositionRequest $request
     * @return Response
     */
    public function createPosition(CreatePositionRequest $request): Response
    {
        $data = $request->post();
        
        // 检查部门是否存在
        $department = DepartmentModel::find($data['department_id']);
        if (!$department) {
            return $this->error('部门不存在');
        }

        // 检查职位编码唯一性
        if (!empty($data['code'])) {
            $exists = DepartmentPositionModel::where('code', $data['code'])
                ->where('department_id', $data['department_id'])
                ->find();
            if ($exists) {
                return $this->error('该部门下职位编码已存在');
            }
        }

        $position = new DepartmentPositionModel();
        $position->save($data);
        
        return $this->success($position, '创建成功');
    }

    /**
     * 更新部门职位
     * @param int $position_id
     * @param UpdatePositionRequest $request
     * @return Response
     */
    public function updatePosition(int $position_id, UpdatePositionRequest $request): Response
    {
        $position = DepartmentPositionModel::find($position_id);
        if (!$position) {
            return $this->error('职位不存在');
        }

        $data = $request->put();
        
        // 检查职位编码唯一性
        if (!empty($data['code']) && $data['code'] !== $position->code) {
            $exists = DepartmentPositionModel::where('code', $data['code'])
                ->where('department_id', $position->department_id)
                ->where('position_id', '<>', $position_id)
                ->find();
            if ($exists) {
                return $this->error('该部门下职位编码已存在');
            }
        }

        $position->save($data);
        
        return $this->success($position, '更新成功');
    }

    /**
     * 删除部门职位
     * @param int $position_id
     * @return Response
     */
    public function deletePosition(int $position_id): Response
    {
        $position = DepartmentPositionModel::find($position_id);
        if (!$position) {
            return $this->error('职位不存在');
        }

        // 检查是否有关联的管理员
        $count = DepartmentAdminModel::where('position_id', $position_id)->count();
        if ($count > 0) {
            return $this->error('该职位下有关联管理员，无法删除');
        }

        $position->delete();
        return $this->success([], '删除成功');
    }

    /**
     * 导出部门数据
     * @return Response
     */
    public function export(): Response
    {
        try {
            $conditions = $this->buildConditions();
            $query = DepartmentModel::with(['leader', 'parentDepartment'])->where($conditions);

            $headers = [
                ['label' => 'ID', 'field' => 'department_id'],
                ['label' => '部门名称', 'field' => 'name'],
                ['label' => '部门编码', 'field' => 'code'],
                ['label' => '父部门', 'field' => 'parent_name'],
                ['label' => '部门层级', 'field' => 'level'],
                ['label' => '排序', 'field' => 'sort'],
                ['label' => '状态', 'field' => 'status', 'format' => 'boolean', 'format_options' => ['true' => '启用', 'false' => '禁用']],
                ['label' => '部门主管', 'field' => 'leader_name'],
                ['label' => '描述', 'field' => 'description'],
                ['label' => '创建时间', 'field' => 'created_at', 'format' => 'datetime', 'format_options' => ['format' => 'Y-m-d H:i:s']],
            ];

            $exportService = new ExportService();
            $estimatedCount = $exportService->estimateCount(function() use ($query) {
                return clone $query;
            });

            $threshold = config('export.large_data_threshold', 10000);
            
            if ($estimatedCount > $threshold) {
                $result = $exportService->createQueueTask(
                    DepartmentModel::class,
                    $query,
                    $headers,
                    '部门数据',
                    'xlsx'
                );
                
                return $this->success($result, $result['message']);
            } else {
                return $exportService->directExport(
                    clone $query,
                    $headers,
                    '部门数据',
                    'xlsx'
                );
            }
        } catch (\Exception $e) {
            return $this->error('导出失败：' . $e->getMessage());
        }
    }

    /**
     * 构建查询条件
     * @return array
     */
    protected function buildConditions(): array
    {
        $conditions = [];
        
        if (request()->has('keyword', 'get', true)) {
            $conditions[] = ['name|code', 'like', "%" . request()->get('keyword') . "%"];
        }

        if (request()->has('status', 'get', true)) {
            $conditions[] = ['status', '=', request()->get('status')];
        }

        if (request()->has('parent_id', 'get', true)) {
            $conditions[] = ['parent_id', '=', request()->get('parent_id')];
        }
        
        return $conditions;
    }
}
