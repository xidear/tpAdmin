<?php

namespace app\request\admin\task;

class Create extends BaseTaskRequest
{
    public function rules(): array
    {
        $rules = $this->getBaseRules();
        $rules['name'] .= '|unique:task';
        return $rules;
    }

    public function message(): array
    {
        $messages = $this->getBaseMessages();
        $messages['name.unique'] = '任务名称已存在';
        return $messages;
    }
}
