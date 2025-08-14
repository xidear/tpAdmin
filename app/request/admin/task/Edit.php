<?php

namespace app\request\admin\task;

class Edit extends BaseTaskRequest
{
    public function rules(): array
    {
        $rules = $this->getBaseRules();
        // 使用路由参数获取task_id
        $id = request()->route('task_id');
        if ($id) {
            $rules['name'] .= '|unique:task,name,' . $id . ',task_id';
        } else {
            // 如果获取不到路由参数，暂时移除unique验证
            // 这种情况理论上不应该发生
            $rules['name'] = str_replace('|unique:task,name,' . $id . ',task_id', '', $rules['name']);
        }
        return $rules;
    }

    public function message(): array
    {
        $messages = $this->getBaseMessages();
        $messages['name.unique'] = '任务名称已存在';
        return $messages;
    }
}
