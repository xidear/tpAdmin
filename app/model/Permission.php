<?php


namespace app\model;

use app\common\BaseModel;
use app\common\enum\MenuPermissionDependencies;
use app\model\MenuPermissionDependency;
use think\model\relation\BelongsToMany;
use think\model\relation\HasMany;

// 中间表模型

class Permission extends BaseModel
{
    protected $pk = 'permission_id';

    /**
     * 批量删除
     * @param $ids
     * @param array $relationList
     * @return bool|string
     */
    public function batchDeleteWithRelation($ids,array $relationList=[]): bool|string
    {

        $menuTitles=(new Menu())->alias("m")
            ->join("menu_permission_dependency d","m.menu_id=d.menu_id","left")
            ->where("d.permission_id","in",$ids)
            ->where("d.type",MenuPermissionDependencies::Required->value)
            ->column("m.title");


        if (!empty($menuTitles)) {
            return $this->false("以下菜单必须使用这些权限[".implode(",",$menuTitles)."]");
        }

        return parent::batchDeleteWithRelation($ids,$relationList);
    }



    /*******************************
     * 菜单相关关联
     ******************************/

    /**
     * 作为必备权限的菜单关联（一对多）
     *
     * 一个权限可以被多个菜单作为"必备权限"
     * @return HasMany
     */
    public function requiredMenus(): HasMany
    {
        return $this->hasMany(Menu::class, 'required_permission_id', 'permission_id');
    }

    /**
     * 与菜单的多对多关联（通过中间表）
     * @return BelongsToMany
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(
            Menu::class,
            'permission_menu',
            'menu_id',
            'permission_id'
        );
    }

    /**
     * 权限菜单中间表关联（一对多）
     * @return HasMany
     */
    public function menuDependencies(): HasMany
    {
        return $this->hasMany(
            MenuPermissionDependency::class,
            'permission_id',
            'permission_id'
        );
    }
    /**
     * 权限菜单中间表关联（一对多）
     * @return HasMany
     */
    public function menu_dependencies(): HasMany
    {
        return $this->hasMany(
            MenuPermissionDependency::class,
            'permission_id',
            'permission_id'
        );
    }
    /*******************************
     * 角色相关关联
     ******************************/

    /**
     * 角色关联（多对多）
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_permission',
            'role_id',
            'permission_id'
        );
    }

    /**
     * 角色权限中间表关联（一对多）
     * @return HasMany
     */
    public function rolePermissions(): HasMany
    {
        return $this->hasMany(
            RolePermission::class,
            'permission_id',
            'permission_id'
        );
    }
}