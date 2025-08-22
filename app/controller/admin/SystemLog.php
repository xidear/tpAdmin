<?php


namespace app\controller\admin;

use app\common\BaseController;
use app\model\Admin as AdminModel;
use app\request\admin\admin\BatchDelete;
use app\request\admin\admin\Create;
use app\request\admin\admin\Delete;
use app\request\admin\admin\Edit;
use app\request\admin\admin\Read;
use think\helper\Str;
use think\Response;

class SystemLog extends BaseController
{


    /**
     * 列表
     * @return Response
     */
    public function index():Response
    {
        $conditions = [];

        // 关键字筛选：搜索用户名、模块、控制器、操作、描述
        if (request()->has('keyword', 'get', true)) {
            $keyword = request()->get('keyword');
            $conditions[] = [
                'username|module|controller|action|description',
                'like',
                "%{$keyword}%"
            ];
        }

        // 管理员ID筛选
        if (request()->has('admin_id', 'get', true)) {
            $conditions[] = ['admin_id', '=', request()->get('admin_id')];
        }

        // 模块筛选
        if (request()->has('module', 'get', true)) {
            $conditions[] = ['module', '=',strtolower(request()->get('module'))];
        }

        // 控制器筛选
        if (request()->has('controller', 'get', true)) {
            $conditions[] = ['controller', '=', ucfirst(request()->get('controller'))];
        }

        // 操作筛选
        if (request()->has('action', 'get', true)) {
            $conditions[] = ['action', '=', Str::camel(request()->get('action'))];
        }

        // 请求方法筛选
        if (request()->has('request_method', 'get', true)) {
            $conditions[] = ['request_method', '=',strtoupper( request()->get('request_method'))];
        }

        // 状态筛选
        if (request()->has('status', 'get', true)) {
            $conditions[] = ['status', '=', request()->get('status')];
        }

        // IP地址筛选
        if (request()->has('ip', 'get', true)) {
            $conditions[] = ['ip', 'like', "%" . request()->get('ip') . "%"];
        }

        // 时间范围筛选
        // 处理时间范围筛选（包含URL解码和边界校验）
        if (request()->has('created_at') && !empty(request()->get('created_at'))) {
            // 获取时间范围数组（确保是数组格式）
            $createdAt = is_array(request()->get('created_at'))
                ? request()->get('created_at')
                : [];

            // 初始化处理后的时间变量
            $startTime = null;
            $endTime = null;

            // 处理开始时间（解码 + 为空格，并校验格式）
            if (!empty($createdAt[0])) {
                // 解码URL中的 + 为空格
                $decodedStartTime = urldecode($createdAt[0]);
                // 简单校验时间格式（可选，根据业务需求调整）
                if (strtotime($decodedStartTime) !== false) {
                    $startTime = $decodedStartTime;
                }
            }

            // 处理结束时间（同上）
            if (!empty($createdAt[1])) {
                $decodedEndTime = urldecode($createdAt[1]);
                if (strtotime($decodedEndTime) !== false) {
                    $endTime = $decodedEndTime;
                }
            }

            // 添加查询条件（仅在时间有效时）
            if ($startTime) {
                $conditions[] = ['created_at', '>=', $startTime];
            }
            if ($endTime) {
                $conditions[] = ['created_at', '<=', $endTime];
            }
        }

        // 执行时间筛选（大于等于）
        if (request()->has('min_execution_time', 'get', true) && is_numeric(request()->get('min_execution_time'))) {
            $conditions[] = ['execution_time', '>=', request()->get('min_execution_time')];
        }

        // 获取日志列表数据
        $list = (new \app\model\SystemLog())->fetchData($conditions);

        return $this->success($list);
    }


    /**
     * @param $id
     * @return Response
     */
    public function read($id): Response
    {
        return $this->success((new \app\model\SystemLog())->fetchOne($id,[
            "append"=>["ua"],
            'with'=>['admin'=>function ($query) {
                $query->field("real_name,username");
            }],
        ]));
    }


    /**
     * @param \app\request\admin\BatchDelete $delete
     * @return Response
     */
    public function batchDelete(\app\request\admin\BatchDelete $delete): Response
    {
        $ids = request()->delete("ids/a");
        $model = new \app\model\SystemLog();
        if ($model->batchDeleteWithRelation($ids)) {
            return $this->success("删除成功");
        } else {
            return $this->error($model->getMessage());
        }
    }


    /**
     * @param $id
     * @return Response
     */
    public function delete($id): Response
    {
        $info=(new \app\model\SystemLog())->fetchOne($id);
        if ($info->isEmpty()){
            return $this->error("找不到指定数据");

        }
        if ($info->delete()) {
            return $this->success("删除成功");
        } else {
            return $this->error("删除失败");
        }
    }


}