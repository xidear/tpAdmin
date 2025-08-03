<?php

namespace app\model;

use app\common\BaseModel;
use think\model\relation\BelongsTo;

/**
 * @property string $created_at
 * @property  int $created_at_int
 */
class AdminToken extends BaseModel
{
    protected $pk = "admin_token_id";

    protected array $append = ["created_at_int"];
    protected string $table = 'admin_token';

    public function getCreatedAtIntAttr($value): false|int
    {
        return strtotime($this->created_at);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }

}