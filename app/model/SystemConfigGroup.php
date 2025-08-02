<?php

namespace app\model;

use app\common\BaseModel;
use think\model\relation\HasMany;

/**
 * 系统配置分组模型
 * @property int $system_config_group_id 分组ID
 * @property string $group_name 分组名称
 * @property int $created_by 创建人ID
 * @property string $created_at 创建时间
 * @property int $updated_by 更新人ID
 * @property string $updated_at 更新时间
 * @property int $sort 排序
 * @property HasMany $configs
 */
class SystemConfigGroup extends BaseModel
{
    // 设置主键
    protected $pk = 'system_config_group_id';

    // 设置表名
    protected string $table = 'system_config_group';

    public function configs(): HasMany
    {
        return $this->hasMany(SystemConfig::class);
    }
}