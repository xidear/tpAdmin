<?php

namespace app\model;

use app\common\BaseModel;
use think\model\relation\BelongsToMany;
use think\model\relation\HasMany;

class Role extends BaseModel
{
    protected $pk = 'role_id';

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany('permission', RolePermission::class, 'permission_id', 'role_id');
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany('admin', AdminRole::class, 'admin_id', 'role_id');
    }

    public function menus(): BelongsToMany
    {
        return $this->belongsToMany('menu', RoleMenu::class, 'menu_id', 'role_id');
    }

    public function role_menus(): \think\model\relation\HasMany
    {
        return $this->hasMany(RoleMenu::class, 'role_id', 'role_id');
    }


    public function roleMenus(): \think\model\relation\HasMany
    {
        return $this->hasMany(RoleMenu::class, 'role_id', 'role_id');
    }

    public function role_permissions(): HasMany{
        return $this->hasMany(RolePermission::class, 'role_id', 'role_id');
    }

    public function rolePermissions(): HasMany{
        return $this->hasMany(RolePermission::class, 'role_id', 'role_id');
    }

}