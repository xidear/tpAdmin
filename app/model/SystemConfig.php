<?php

namespace app\model;

use app\common\BaseModel;
use app\common\enum\YesOrNo;
use think\model\relation\BelongsTo;



class SystemConfig extends BaseModel
{
    // 主键设置
    protected $pk = 'system_config_id';

    // 时间戳设置（TP8 严格类型）
    protected bool $autoWriteTimestamp = true;
    protected string $createTime = 'created_at';
    protected string $updateTime = 'updated_at';

    // 类型转换（TP8 正确配置）
    protected array $type = [
        'options' => 'json',
        'sort' => 'integer',
        'is_enabled' => 'integer',
        'config_type' => 'integer',
        'is_system' => 'integer',
    ];

    /**
     * 从缓存获取配置值
     * @param string $key 配置键名
     * @param mixed|null $default 默认值
     */
    public static function getCacheValue(string $key, mixed $default = null): array
    {
        $cache = cache('system_config');
        if (empty($cache)) {
            self::refreshCache();
            $cache = cache('system_config') ?: [];
        }
        return $cache[$key] ?? $default;
    }

    /**
     * 刷新配置缓存
     * @return bool
     */
    public static function refreshCache(): bool
    {
        $configs = (new SystemConfig)->where('is_enabled', YesOrNo::Yes->value)
            ->column('config_value', 'config_key');
        return cache('system_config', $configs) !== false;
    }

    /**
     * 模型事件：新增后刷新缓存
     * @param self $model
     */
    protected static function onAfterInsert(self $model): void
    {
        self::refreshCache();
    }

    /**
     * 模型事件：更新后刷新缓存
     * @param self $model
     */
    protected static function onAfterUpdate(self $model): void
    {
        self::refreshCache();
    }

    /**
     * 模型事件：删除后刷新缓存
     * @param self $model
     */
    protected static function onAfterDelete(self $model): void
    {
        self::refreshCache();
    }

    /**
     * 配置分组
     * @return BelongsTo
     */
    public function config_group(): BelongsTo
    {
        return $this->belongsTo(SystemConfigGroup::class);
    }

    /**
     * 配置分组
     * @return BelongsTo
     */
    public function configGroup(): BelongsTo
    {
        return $this->belongsTo(SystemConfigGroup::class);
    }
}
