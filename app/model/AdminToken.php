<?php

namespace app\model;

use app\common\BaseModel;
use think\model\relation\BelongsTo;

class AdminToken extends BaseModel
{
    protected $pk = "id";


    protected string $table = 'admin_token';

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }

}