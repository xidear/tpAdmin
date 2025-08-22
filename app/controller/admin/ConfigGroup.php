<?php


namespace app\controller\admin;

use app\common\BaseController;
use app\model\SystemConfigGroup;
use app\request\admin\config_group\Create;
use app\request\admin\Delete;
use app\request\admin\Read;
use app\request\admin\config_group\Update;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Response;

class ConfigGroup extends BaseController
{
    /**
     * 配置分组列表
     * @return Response
     */
    public function index(): Response
    {


        $conditions = [];
        $params = request()->param();
        if (!empty($params['group_name'])) {
            $conditions[] = ["group_name", "like", "%" . $params['group_name'] . "%"];
        }

        $configGroups = (new SystemConfigGroup())->fetchData($conditions,config: [
            'orderBy' => "sort",
            'orderDir' => 'desc'
        ]);
        return $this->success($configGroups);
    }

    /**
     * 获取配置分组详情
     * @param int $group_id
     * @param Read $read
     * @return Response
     */
    public function read(int $group_id, Read $read): Response
    {
        $info = (new SystemConfigGroup())->fetchOne($group_id,['with'=>['configs']]);
        if ($info->isEmpty()) {
            return $this->error("未找到指定配置分组");
        }
        return $this->success($info);
    }

    /**
     * 新增配置分组
     * @param Create $create
     * @return Response
     */
    public function create(Create $create): Response
    {
        $params = request()->post();

        // 补充创建人ID（假设当前登录用户ID通过BaseController的getLoginAdminId方法获取）
        $params['created_by'] = request()->adminId;
        $params['updated_by'] = request()->adminId;

        $model = new SystemConfigGroup();
        $result = $model->intelligentCreate($params);

        if (!empty($result)&&!$result->isEmpty()) {
            return $this->success($result, "配置分组创建成功");
        }

        return $this->error($model->getMessage() ?: "配置分组创建失败");
    }

    /**
     * 更新配置分组
     * @param int $group_id
     * @param Update $update
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function update(int $group_id, Update $update): Response
    {
        $params = request()->put();

        // 补充更新人ID
        $params['updated_by'] = request()->adminId;

        $model = new SystemConfigGroup();
        $info = $model->fetchOne($group_id);
        if ($info->isEmpty()) {
            return $this->error("未找到指定配置分组");
        }

        if ($info->save($params,[],true)) {
            return $this->success($info);
        }

        return $this->error($info?->getMessage() ?: "配置分组更新失败");
    }

    /**
     * 删除配置分组
     * @param int $group_id
     * @param Delete $delete
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function delete(int $group_id, Delete $delete): Response
    {
        $model = (new SystemConfigGroup())->fetchOne($group_id);
        if ($model->isEmpty()) {
            return $this->error("指定配置分组不存在");
        }

        if ($model->configs()->count() > 0) { return $this->error("该分组下有关联配置，不允许删除"); }

        if ($model->delete()) {
            return $this->success("配置分组删除成功");
        }

        return $this->error($model->getMessage() ?: "配置分组删除失败");
    }
}