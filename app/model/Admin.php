<?php

namespace app\model;

use app\common\BaseModel;
use think\model\relation\BelongsToMany;

/**
 * @property string $password
 * @property string $username
 * @property string $type
 */
class Admin extends BaseModel
{
    protected $pk = 'admin_id';

    /**
     * 关联角色
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'admin_role', 'role_id', 'admin_id');
    }

    /**
     * 是否超管
     * @return bool
     */
    public function isSuper(): bool
    {
        return $this->getKey() == 1;
    }
}