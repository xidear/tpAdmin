<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\model\SystemConfig;
use app\model\SystemConfigGroup;
use app\common\enum\ConfigType;
use app\common\enum\YesOrNo;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Response;
use think\Request;

class ConfigForm extends BaseController
{
    /**
     * 获取所有配置分组及表单结构
     * @return Response
     */
    public function getForm(): Response
    {
        // 获取所有分组及其配置项
        try {
            $groups = (new \app\model\SystemConfigGroup)->with(['configs' => function ($query) {
                $query->where('is_enabled', YesOrNo::Yes->value)
                    ->order('sort', 'desc');
            }])->order('sort', 'desc')->select();
        } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
            return $this->error($e->getMessage());
        }

        $form = [];
        foreach ($groups as $group) {
            $formGroup = [
                'group_id' => $group->system_config_group_id,
                'group_name' => $group->group_name,
                'fields' => []
            ];

            foreach ($group->configs as $config) {
                $field = [
                    'key' => $config->config_key,
                    'label' => $config->config_name,
                    'type' => $config->config_type,
                    'value' => $config->config_value,
                    'options' => $config->options ?: [],
                    'required' => $config->is_system==YesOrNo::Yes->value, // 可根据需要扩展
                    'placeholder' => $config->remark ?: '',
                    'rules' => $config->vue_rules
                ];

                // 根据类型添加特殊属性
                $field = $this->enhanceFieldByType($field, $config->config_type);

                $formGroup['fields'][] = $field;
            }

            $form[] = $formGroup;
        }

        return $this->success($form);
    }

    /**
     * 分组批量保存配置
     * @param Request $request
     * @return Response
     */
    public function saveByGroup(Request $request): Response
    {
        $data = $request->post();

        if (empty($data)) {
            return $this->error('保存数据不能为空');
        }

        try {
            foreach ($data as $key => $value) {
                $config = SystemConfig::where('config_key', $key)->find();
                if (!$config) {
                    continue;
                }

                // 验证数据类型
                if (!ConfigType::validateValue($config->config_type, $value)) {
                    return $this->error("配置项 {$config->config_name} 数据格式不正确");
                }
                $config->save();
            }

            return $this->success([], '保存成功');
        } catch (\Exception $e) {
            return $this->error('保存失败：' . $e->getMessage());
        }
    }

    /**
     * 刷新配置缓存
     * @return Response
     */
    public function refreshCache(): Response
    {
        try {
            $result = SystemConfig::refreshCache();
            if ($result) {
                return $this->success([], '缓存刷新成功');
            } else {
                return $this->error('缓存刷新失败');
            }
        } catch (\Exception $e) {
            return $this->error('刷新失败：' . $e->getMessage());
        }
    }





    /**
     * 根据类型增强字段属性
     * @param array $field
     * @param int $type
     * @return array
     */
    private function enhanceFieldByType(array $field, int $type): array
    {
        switch ($type) {
            case ConfigType::TEXTAREA->value:
                $field['rows'] = 4;
                break;
            case ConfigType::RICH_TEXT->value:
                $field['height'] = 300;
                break;
            case ConfigType::IMAGE->value:
            case ConfigType::IMAGES->value:
                $field['accept'] = 'image/*';
                $field['multiple'] = $type === ConfigType::IMAGES->value;
                break;
            case ConfigType::FILE->value:
            case ConfigType::FILES->value:
                $field['multiple'] = $type === ConfigType::FILES->value;
                break;
            case ConfigType::COLOR->value:
                $field['format'] = 'hex';
                break;
        }

        return $field;
    }
}