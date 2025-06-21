<?php
namespace app\model;

use app\common\BaseModel;

class Permission extends BaseModel
{
    protected $pk = 'permission_id';

    const string TYPE_MENU = 'MENU';
    const string TYPE_BUTTON = 'BUTTON';
    const string TYPE_API = 'API';
}