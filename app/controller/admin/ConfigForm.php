<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\model\SystemConfig;
use app\model\SystemConfigGroup;
use app\common\enum\config\ConfigType;
use app\common\enum\YesOrNo;
use app\request\admin\config_form\SaveByGroup;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Cache;
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
                $value=$config->config_value;
                if (is_string($config->config_value)&&json_validate($config->config_value)){
                    $value=json_decode($value,true);
                }
                $field = [
                    'key' => $config->config_key,
                    'label' => $config->config_name,
                    'type' => $config->config_type,
                    'value' => $value,
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
     * @param SaveByGroup $request
     * @return Response
     */
    public function saveByGroup(SaveByGroup $request): Response
    {
        $data= $request->put();

        if (empty($data)) {
            return $this->error('保存数据不能为空');
        }
        $groupId = $data['group_id'];
        $fields = $data['fields'];

//        halt($data);
        try {
            foreach ($fields as $key => $value) {
                $config = (new \app\model\SystemConfig)->where('config_key', $key)->where("system_config_group_id",$groupId)->findOrEmpty();

                if (!$config||$config->isEmpty()) {
                    continue;
                }


                // 验证数据类型
                if (!ConfigType::validateValue($config->config_type, $value,$config->is_system!=YesOrNo::Yes->value)) {
                    return $this->error("配置项 {$config->config_name} 数据格式不正确");
                }
                if (is_array($value)){
                    $value=json_encode($value);
                }
                $config->save(['config_value' => $value], ['config_key' => $key]);
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
            $cacheTime=SystemConfig::getValueByKey("config_cache_time")?:0;
            if (empty($cacheTime)) {
                return $this->error('系统配置不缓存');
            }



            $result = SystemConfig::refreshCache();
            if ($result) {
                return $this->success($result, '缓存刷新成功');
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