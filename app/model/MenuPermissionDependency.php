<?php
namespace app\model;

use app\common\BaseModel;
use app\common\BasePivot;

class MenuPermissionDependency extends BasePivot
{
    protected $pk = 'dependency_id';

    protected string $table = 'menu_permission_dependency';
}