<?php

namespace app\model;

use app\common\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 部门职位模型
 * @property int $position_id
 * @property int $department_id
 * @property string $name
 * @property string $code
 * @property string $description
 * @property int $sort
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_type
 */
class DepartmentPosition extends BaseModel
{
    protected $pk = 'position_id';
    protected $name = 'department_position';

    use SoftDelete;

    protected string $deleteTime = 'deleted_at';

    /**
     * 关联部门
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    /**
     * 关联部门管理员
     */
    public function departmentAdmins()
    {
        return $this->hasMany(DepartmentAdmin::class, 'position_id', 'position_id');
    }
}
