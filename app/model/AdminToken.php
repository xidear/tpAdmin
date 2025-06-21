<?php
namespace app\model;

use app\common\BaseModel;
use app\common\BasePivot;

class AdminToken extends BaseModel
{
    protected $pk ="id";
    public function admin(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(Admin::class,'admin_id','admin_id');
    }

}