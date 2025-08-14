<?php

namespace app\request\admin\task;

use app\common\BaseRequest;
use app\common\enum\Status;
use app\common\enum\TaskType;
use app\common\enum\TaskPlatform;

class BaseTaskRequest extends BaseRequest
{
    /**
     * 获取状态验证规则
     */
    protected function getStatusRule(): string
    {
        $statusValues = Status::getKeyListString();
        return 'in:' . $statusValues;
    }

    /**
     * 获取类型验证规则
     */
    protected function getTypeRule(): string
    {
        $typeValues = TaskType::getKeyListString();
        return 'require|in:' . $typeValues;
    }

    /**
     * 获取平台验证规则
     */
    protected function getPlatformRule(): string
    {
        $platformValues = TaskPlatform::getKeyListString();
        return 'require|in:' . $platformValues;
    }

    /**
     * 基础验证规则
     */
    protected function getBaseRules(): array
    {
        return [
            'name' => 'require|max:100',
            'description' => 'max:500',
            'type' => $this->getTypeRule(),
            'content' => 'require|max:2000',
            'schedule' => 'require|max:50|checkCrontab',
            'platform' => $this->getPlatformRule(),
            'exec_user' => 'max:50',
            'timeout' => 'integer|min:1|max:86400',
            'retry' => 'integer|min:0|max:10',
            'interval' => 'integer|min:0|max:3600',
            'status' => $this->getStatusRule(),
            'sort' => 'integer|min:0|max:1000',
        ];
    }

    /**
     * 基础错误消息
     */
    protected function getBaseMessages(): array
    {
        return [
            'name.require' => '任务名称不能为空',
            'name.max' => '任务名称不能超过100个字符',
            'description.max' => '任务描述不能超过500个字符',
            'type.require' => '任务类型必须选择',
            'type.in' => '任务类型选择错误',
            'content.require' => '任务内容不能为空',
            'content.max' => '任务内容不能超过2000个字符',
            'schedule.require' => '调度规则不能为空',
            'schedule.max' => '调度规则不能超过50个字符',
            'schedule.checkCrontab' => '调度规则格式不正确',
            'platform.require' => '运行平台必须选择',
            'platform.in' => '运行平台选择错误',
            'exec_user.max' => '执行用户不能超过50个字符',
            'timeout.integer' => '超时时间必须为整数',
            'timeout.min' => '超时时间不能小于1秒',
            'timeout.max' => '超时时间不能大于86400秒',
            'retry.integer' => '重试次数必须为整数',
            'retry.min' => '重试次数不能小于0',
            'retry.max' => '重试次数不能大于10次',
            'interval.integer' => '重试间隔必须为整数',
            'interval.min' => '重试间隔不能小于0',
            'interval.max' => '重试间隔不能大于3600秒',
            'status.in' => '状态设置错误',
            'sort.integer' => '排序必须为整数',
            'sort.min' => '排序不能小于0',
            'sort.max' => '排序不能大于1000',
        ];
    }
}