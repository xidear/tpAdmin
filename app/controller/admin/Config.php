<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\common\enum\ConfigGroup;
use app\model\SystemConfig;
use app\request\admin\config\CreateConfig;
use app\request\admin\config\UpdateConfig;
use think\db\exception\DbException;
use think\Response;

class Config extends BaseController
{
    /**
     * 获取所有配置分组列表
     * @return Response
     */
    public function index(): Response
    {


        $conditions = [];
        $params = $this->request->param();
        if (!empty($params['config_key'])) {
            $conditions[] = ["config_key", "like", "%" . $params['config_key'] . "%"];
        }
        if (!empty($params['config_name'])) {
            $conditions[] = ["config_name", "like", "%" . $params['config_name'] . "%"];
        }

        if (!empty($params['system_config_group_id'])) {
            $conditions[] = ["system_config_group_id", "=", $params['system_config_group_id']];
        }

        $roles = (new SystemConfig())->fetchData(
            conditions: $conditions,
            config: [
                'with' => ['config_group' => function ($query) {
                    $query->field('group_name,system_config_group_id');
                }]
            ]
        );
        return $this->success($roles);
    }


    /**
     * 新增配置项
     * @param CreateConfig $request
     * @return Response
     */
    public function create(CreateConfig $request): Response
    {
        $data = $request->post();
        // 调用公共方法处理数据（标记为新增）
        $processedData = $this->processConfigData($data, true);

        // 新增配置项
        $config = SystemConfig::create($processedData);

        if ($config->isEmpty()) {
            return $this->error('配置项新增失败');
        }
        // 刷新缓存
        SystemConfig::refreshCache();

        return $this->success($config, '配置项新增成功');
    }

    /**
     * 公共方法：处理配置项数据（创建和更新复用）
     * @param array $data 原始数据
     * @param bool $isCreate 是否为新增操作
     * @return array 处理后的数据集
     */
    protected function processConfigData(array $data, bool $isCreate): array
    {
        $adminId = request()->adminId;
        $data['updated_by'] = $adminId;
        if ($isCreate) {
            $data['created_by'] = $adminId;
        }

        return $data;
    }

    /**
     * 编辑配置项
     * @param int $system_config_id
     * @param UpdateConfig $request
     * @return Response
     */
    public function update(int $system_config_id, UpdateConfig $request): Response
    {
        // 验证配置项是否存在
        $config = (new SystemConfig)->findOrEmpty($system_config_id);
        if ($config->isEmpty()) {
            return $this->error("配置项不存在：{$system_config_id}");
        }

        $data = $request->put();
        // 调用公共方法处理数据（标记为更新）
        $processedData = $this->processConfigData($data, false);
        if ($config->update($processedData)) {
            // 刷新缓存
            SystemConfig::refreshCache();

            return $this->success($config, '配置项更新成功');
        }


        return $this->error('配置项更新失败');

    }

    /**
     * 获取单个配置项详情
     * @param int $system_config_id
     * @return Response
     */
    public function read(int $system_config_id): Response
    {
        $config = (new SystemConfig)->fetchOne($system_config_id, [
            'with' => 'config_group'
        ]);
        if ($config->isEmpty()) {
            return $this->error("配置项不存在：{$system_config_id}");
        }


        return $this->success($config);
    }
}