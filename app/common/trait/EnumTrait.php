<?php


namespace app\common\trait;


trait EnumTrait
{
    /**
     * 验证值是否为合法的配置类型
     * @param int $value 枚举值
     * @return bool 合法返回true，否则false
     */
    public static function isValidValue(int $value): bool
    {
        return self::tryFrom($value) !== null;
    }

    /**
     * 获取关联数组
     * 返回[[
     * key=>value
     * ]]
     * @return array
     */
    public static function getItems(): array
    {
        if (!empty(self::getList())) {
            return array_column(self::getList(), "value", "key");
        }
        return [];
    }

    /**
     * 获取顺序数组
     * 必须在使用此trait的类中实现的方法。
     */
    public function getList(): array
    {
        return [];
    }


    /**
     * 根据 1  获取 正常
     * @param $key
     * @return string
     */
    public static function getValue($key): string
    {
        if (key_exists($key, self::getItems())) {
            return self::getItems()[$key];
        }
        return "";
    }


    /**
     * 根据正常  获取 1
     * @param $value
     * @return int|string|null
     */
    public static function getKey($value): int|null|string
    {
//        翻转数组 生成 [
//            '正常'=>1,
//            '禁用'=>2,
//        ]

        $array = array_flip(self::getItems());
        if (key_exists($value, $array)) {
            return $array[$value];
        }
        return null;
    }


    /**
     * 获取 [1,2,3]这种数组
     * @return array
     */
    public static function getKeyList(): array
    {
        return array_column(self::getList(),"key");
//        return array_keys(self::getItems());
    }


    /**
     * 获取 [正常,禁用,全部] 这种数组
     * @return array
     */
    public static function getValueList(): array
    {
        return array_column(self::getList(),"value");
//        return array_values(self::getItems());
    }


    /**
     * 获取 正常,禁用,全部 这种字符串
     * @param string $sep
     * @return string
     */
    public static function getValueListString(string $sep = ";"): string
    {
        return implode($sep, self::getValueList());
    }

    /**
     * 获取1,2,3这种字符串
     * @param string $sep
     * @return string
     */
    public static function getKeyListString(string $sep = ","): string
    {
        return implode($sep, self::getKeyList());
    }

}