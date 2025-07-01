<?php
namespace app\model;

use app\common\BaseModel;
use app\service\PermissionService;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;

class Menu extends BaseModel
{
    protected $pk = 'menu_id';

    // 关联必需权限
    public function requiredPermission(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(Permission::class, 'required_permission_id');
    }

    // 关联依赖权限
    public function dependencies(): \think\model\relation\HasMany
    {
        return $this->hasMany(MenuPermissionDependency::class, 'menu_id');
    }

    /**
     * 获取用户可访问菜单
     * @param $adminId
     * @return array
     */
    public static function getUserMenus($adminId): array
    {
        if (empty($adminId)){
            return [];
        }
        try {
            $permissionIds = (new PermissionService)->getAdminPermissions($adminId);

            return self::hasWhere('requiredPermission', function ($query) use ($permissionIds) {
                $query->whereIn('permission_id', $permissionIds);
            })->append(["meta"])
                ->hidden(["order_num","is_link","visible","link_url","is_full","is_affix","is_keep_alive","required_permission_id","created_at","updated_at"])
                ->order("order_num asc")
                ->selectOrFail()
                ->each(function($item){

                if(!empty($item['redirect'])){
                    unset($item['component']);
                }
                return $item;
            })->toArray();
        } catch (DataNotFoundException|ModelNotFoundException $e) {
            (new Menu)->reportError($e->getMessage(),(array)$e,$e->getCode());
            return [];
        }
    }

    /**
     * 获取菜单meta数据
     * @param [type] $value
     * @param [type] $data
     * @return array
     */ 
    public function  getMetaAttr($value,$data):array{

        return  [
            'icon'=>$data['icon'],
            'title'=>$data['title'],
            'isLink'=>$data['link_url']?:false,
            'isHide'=> !($data['visible'] == 1),
            'isFull'=> $data['is_full']==1,
            'isAffix'=> $data['is_affix']==1,
            'isKeepAlive'=> $data['is_keep_alive']==1,
        ];

    }

    /**
     * 获取用户可访问菜单树
     * @param $adminId
     * @return array
     */
    public static function getUserMenuTree($adminId): array
    {
        $menus = self::getUserMenus($adminId);
        return self::buildTree($menus);
    }

    /**
     * 将扁平的菜单数据转换为树形结构
     *
     * @param array $items 扁平的菜单数据数组
     * @param int $parentId 父级 ID，默认为 0
     * @return array 树形结构的菜单数据
     */
    public static function buildTree(array $items, $parentId = 0)
    {
        $tree = [];
        foreach ($items as $item) {
            if ($item['parent_id'] == $parentId) {
                $children = self::buildTree($items, $item['menu_id']);
                if ($children) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }
}