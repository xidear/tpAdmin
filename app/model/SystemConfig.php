<?php

namespace app\model;

use app\common\BaseModel;
use app\common\enum\ConfigType;
use app\common\enum\YesOrNo;
use think\facade\Cache;
use think\model\relation\BelongsTo;


/**
 * @property int $config_type
 * @property array $rules
 * @property array $vue_rules
 * @method static getConfigValueByConfigKey(string $key)
 */
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
     * @param array $keys 配置键名数组
     * @param mixed|null $default 默认值
     * @return mixed
     */
    public static function getCacheValues(array $keys, mixed $default = null): array
    {
        $array=[];
        foreach ($keys as $key) {
            $array[$key] = self::getCacheValue($key,!empty($default[$key])?$default[$key]:null);
        }
        return $array;
    }


    /**
     * 从缓存获取配置值
     * @param string $key 配置键名
     * @param mixed|null $default 默认值
     * @return mixed
     */
    public static function getCacheValue(string $key, mixed $default = null): mixed
    {
        $cache = cache('system_config');
        if (empty($cache)) {
            if (!self::refreshCache()){
                return self::getValue($key)?:$default;
            }
            $cache = cache('system_config') ?: [];
        }
        return $cache[$key] ?? $default;
    }

    /**
     * 刷新配置缓存
     */
    public static function refreshCache(): bool
    {

        $cacheTime=self::getValue("config_cache_time")??0;
        if ($cacheTime>0){
            $configs = (new SystemConfig)->where('is_enabled', YesOrNo::Yes->value)
                ->column('config_value', 'config_key');
           return  Cache::set("system_config",$configs,$cacheTime);
        }
        return  false;
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

    private static function getValue(string $key)
    {
        return self::getConfigValueByConfigKey($key);
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

    public function getVueRulesAttr(): array
    {
        return $this->getVueRules($this->config_type,$this->rules?:[]);
    }

    /**
     * 生成前端验证规则
     * @param int $type
     * @param array|null $rules
     * @return array
     */
    public function getVueRules(int $type,?array $rules=[]): array
    {

        if (empty($type)){
            $type=$this?->config_type??0;
        }
        if (empty($type)){
            return [];
        }
        if (empty($rules)){
            $rules=$this->rules;
        }
        $defaultRules=ConfigType::getVueRules($this->config_type);
        $defaultMap = [];
        $customMap = [];

        if (!empty($defaultRules)) {
            // 2. 过滤无效规则（仅保留有label的有效项）
            $validDefaults = array_filter($defaultRules, fn($rule) => !empty($rule['label']));
            // 3. 处理空数组情况，避免array_column生成错误的键值
            if (!empty($validDefaults)) {
                $defaultMap = array_column($validDefaults, null, 'label');
            }
        }

        if (!empty($rules)) {
            $validCustom = array_filter($rules, fn($rule) => !empty($rule['label']));
            if (!empty($validCustom)) {
                $customMap = array_column($validCustom, null, 'label');
            }
        }

        // 4. 合并规则（处理双方都为空的极端情况）
        if (empty($defaultMap) && empty($customMap)) {
            return []; // 双方都为空时直接返回空数组
        }

        $mergedRules = array_merge($defaultMap, $customMap);

        // 转回索引数组，返回给前端（保持与前端EnumItem[]格式一致）
        return array_values($mergedRules);

    }

}
