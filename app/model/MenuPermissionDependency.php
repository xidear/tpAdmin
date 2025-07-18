<?php
namespace app\model;

use app\common\BaseModel;
use app\common\BasePivot;

class MenuPermissionDependency extends BasePivot
{
    protected $pk = 'dependency_id';

    protected string $table = 'menu_permission_dependency';



    public function menu(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(Menu::class,'menu_id','menu_id');
    }
    public function permission(): \think\model\relation\BelongsTo{
        return $this->belongsTo(Permission::class,'permission_id','permission_id');
    }


}