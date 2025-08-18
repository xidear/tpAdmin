<?php

namespace app\model;

use app\common\BasePivot;

/**
 * 部门管理员关联模型
 * @property int $id
 * @property int $department_id
 * @property int $admin_id
 * @property int $position_id
 * @property int $is_leader
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_type
 */
class DepartmentAdmin extends BasePivot
{
    protected $pk = 'id';
    protected $name = 'department_admin';

    /**
     * 关联部门
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    /**
     * 关联管理员
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }

    /**
     * 关联职位
     */
    public function position()
    {
        return $this->belongsTo(DepartmentPosition::class, 'position_id', 'position_id');
    }
}
