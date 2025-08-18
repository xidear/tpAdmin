<?php

namespace app\model;

use app\common\BaseModel;
use app\common\trait\TreeTrait;
use think\model\concern\SoftDelete;

/**
 * 部门模型
 * @property int $department_id
 * @property string $name
 * @property string $code
 * @property int $parent_id
 * @property int $level
 * @property string $path
 * @property int $sort
 * @property int $status
 * @property string $description
 * @property int $leader_id
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_type
 */
class Department extends BaseModel
{
    protected $pk = 'department_id';
    protected $name = 'department';

    use SoftDelete, TreeTrait;

    protected string $deleteTime = 'deleted_at';

    /**
     * 初始化树形结构配置
     */
    protected function initialize()
    {
        parent::initialize();
        
        // 设置树形结构配置
        $this->setTreeConfig([
            'parentKey' => 'parent_id',
            'primaryKey' => 'department_id',
            'pathKey' => 'path',
            'levelKey' => 'level',
            'nameKey' => 'name',
            'childrenKey' => 'children',
            'pathSeparator' => ',',
            'sortKey' => 'sort',
            'statusKey' => 'status',
            'deletedAtKey' => 'deleted_at',
        ]);
    }

    /**
     * 关联子部门
     */
    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id', 'department_id');
    }

    /**
     * 关联父部门
     */
    public function parentDepartment()
    {
        return $this->belongsTo(Department::class, 'parent_id', 'department_id');
    }

    /**
     * 关联部门主管
     */
    public function leader()
    {
        return $this->belongsTo(Admin::class, 'leader_id', 'admin_id');
    }

    /**
     * 关联部门职位
     */
    public function positions()
    {
        return $this->hasMany(DepartmentPosition::class, 'department_id', 'department_id');
    }

    /**
     * 关联部门管理员
     */
    public function admins()
    {
        return $this->belongsToMany(Admin::class, 'department_admin', 'department_id', 'admin_id');
    }

    /**
     * 获取树状结构数据
     */
    public static function getTreeData($conditions = [])
    {
        return (new self())->getAllTree(['*'], $conditions);
    }

    /**
     * 获取部门路径
     */
    public function getPathText()
    {
        return $this->getPathText($this->department_id);
    }

    /**
     * 获取所有子部门ID（包括自己）
     */
    public function getAllChildrenIds()
    {
        return $this->getAllDescendantIds($this->department_id);
    }

    /**
     * 检查是否有子部门
     */
    public function hasChildren()
    {
        return $this->hasChildren($this->department_id);
    }

    /**
     * 检查是否可以删除
     */
    public function canDelete()
    {
        // 检查是否有子部门
        if ($this->hasChildren()) {
            return false;
        }
        
        // 检查是否有关联的管理员
        if (DepartmentAdmin::where('department_id', $this->department_id)->count() > 0) {
            return false;
        }
        
        return true;
    }

    /**
     * 更新部门路径
     */
    public function updatePath()
    {
        return $this->updateNodePath($this->department_id);
    }

    /**
     * 更新子部门路径
     */
    public function updateChildrenPath()
    {
        $this->updateChildrenPaths($this->department_id, explode(',', $this->path));
    }
}
