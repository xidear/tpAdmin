<?php
namespace app\model;

use app\common\BaseModel;
use app\common\BasePivot;

class AdminRole extends BasePivot
{
    public function admin(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(Admin::class,'admin_id','admin_id');
    }

    public function role(): \think\model\relation\BelongsTo{
        return $this->belongsTo(Role::class,'role_id','role_id');
    }
}