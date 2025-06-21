<?php
namespace app\model;

use app\common\BaseModel;
use think\model\Pivot;

class RoleMenu extends Pivot
{
    public function role(): \think\model\relation\BelongsTo{
        return $this->belongsTo(Role::class,'role_id','role_id');
    }
    public function menu(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(Menu::class,'menu_id','menu_id');
    }


}