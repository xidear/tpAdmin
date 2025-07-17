<?php
namespace app\model;

use app\common\BaseModel;

class Role extends BaseModel
{
    protected $pk = 'role_id';

    public function permission(){
        return $this->belongsToMany('permission','role_permission','permission_id','role_id');
    }

}