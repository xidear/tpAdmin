<?php
namespace app\model;

use app\common\BaseModel;
use think\model\relation\BelongsTo;
use think\model\relation\BelongsToMany;
use think\model\relation\HasMany;

class Permission extends BaseModel
{
    protected $pk = 'permission_id';

    const string TYPE_MENU = 'MENU';
    const string TYPE_BUTTON = 'BUTTON';



    const string TYPE_API = 'API';


    /**
     * 批量删除
     * @param $ids
     * @return bool|string
     */
    public function batchDeleteWithRelation($ids): bool|string
    {

        $menuTitles=(new Menu())->whereIn("required_permission_id",$ids)->column("title");
        if (!empty($menuTitles)) {
            return $this->false("以下菜单必须使用这些权限[{".implode(",",$menuTitles)."}]");
        }

        return parent::batchDeleteWithRelation($ids);
    }

    /**
     * 必须此权限的菜单标题列表
     * @return string
     */
    public function getRequiredMenusNameToString(): string
    {

        $titles=$this->requiredMenus()->column('title');
        return implode(";",$titles);


    }

    /**
     * 角色关联（多对多）
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission', 'role_id', 'permission_id');
    }

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
        return $this->belongsToMany(Menu::class, 'permission_menu', 'menu_id', 'permission_id');
    }


}