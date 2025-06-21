<?php
namespace app\model;

use app\common\BaseModel;
use app\common\BasePivot;

class RolePermission extends BasePivot
{
    public function permission(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(Permission::class,'admin_id','admin_id');
    }

    public function role(): \think\model\relation\BelongsTo{
        return $this->belongsTo(Role::class,'role_id','role_id');
    }
}