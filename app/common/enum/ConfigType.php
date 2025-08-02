<?php

namespace app\common\enum;

use app\common\trait\EnumTrait;

enum ConfigType: int
{
    // 基础输入类
    case TEXT = 1;             // 单行文本框
    case TEXTAREA = 2;         // 多行文本域
    case NUMBER = 3;           // 数字输入框
    case PASSWORD = 4;         // 密码框
    case URL = 5;              // 网址输入框
    case EMAIL = 6;            // 邮箱输入框
    case PHONE = 7;            // 手机号输入框

    // 选择类
    case SWITCH = 10;          // 开关（是/否）
    case RADIO = 11;           // 单选框组
    case CHECKBOX = 12;        // 复选框组
    case SELECT = 13;          // 下拉选择器
    case MULTI_SELECT = 14;    // 多选下拉框
    case CASCADER = 15;        // 级联选择器

    // 媒体类
    case IMAGE = 20;           // 单图上传
    case IMAGES = 21;          // 多图上传
    case VIDEO = 22;           // 视频上传
    case FILE = 23;            // 单文件上传
    case FILES = 24;           // 多文件上传

    // 富文本与特殊格式
    case RICH_TEXT = 30;       // 富文本编辑器
    case MARKDOWN = 31;        // Markdown编辑器
    case JSON = 32;            // JSON编辑器
    case CODE = 33;            // 代码编辑器
    case COLOR = 34;           // 颜色选择器
    case DATE = 35;            // 日期选择器
    case DATETIME = 36;        // 日期时间选择器
    case TIME = 37;            // 时间选择器

    // 结构化数据
    case KEY_VALUE = 40;       // 键值对配置
    case TABLE = 41;           // 表格配置
    case TREE = 42;            // 树形结构配置

    use EnumTrait;

    // 正则表达式常量（前后端共用，确保规则一致）
    private const string REGEX_PHONE = '/^1[3-9]\d{9}$/';         // 手机号正则
    private const string REGEX_DATE = '/^\d{4}-\d{2}-\d{2}$/';     // 日期格式（YYYY-MM-DD）
    private const string REGEX_DATETIME = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/'; // 日期时间格式
    private const string REGEX_TIME = '/^\d{2}:\d{2}:\d{2}$/';     // 时间格式（HH:MM:SS）
    private const string REGEX_COLOR = '/^#([0-9A-F]{3}){1,2}$/i'; // 颜色值格式（#RGB或#RRGGBB）


    /**
     * 获取所有配置类型列表（供前端选择）
     * @return array 格式：[{key: 值, value: 文本说明}, ...]
     */
    public static function getList(): array
    {
        return [
            // 基础输入类
            ['key' => self::TEXT->value, 'value' => '单行文本框'],
            ['key' => self::TEXTAREA->value, 'value' => '多行文本域'],
            ['key' => self::NUMBER->value, 'value' => '数字输入框'],
            ['key' => self::PASSWORD->value, 'value' => '密码框'],
            ['key' => self::URL->value, 'value' => '网址输入框'],
            ['key' => self::EMAIL->value, 'value' => '邮箱输入框'],
            ['key' => self::PHONE->value, 'value' => '手机号输入框'],

            // 选择类
            ['key' => self::SWITCH->value, 'value' => '开关（是/否）'],
            ['key' => self::RADIO->value, 'value' => '单选框组'],
            ['key' => self::CHECKBOX->value, 'value' => '复选框组'],
            ['key' => self::SELECT->value, 'value' => '下拉选择器'],
            ['key' => self::MULTI_SELECT->value, 'value' => '多选下拉框'],
            ['key' => self::CASCADER->value, 'value' => '级联选择器'],

            // 媒体类
            ['key' => self::IMAGE->value, 'value' => '单图上传'],
            ['key' => self::IMAGES->value, 'value' => '多图上传'],
            ['key' => self::VIDEO->value, 'value' => '视频上传'],
            ['key' => self::FILE->value, 'value' => '单文件上传'],
            ['key' => self::FILES->value, 'value' => '多文件上传'],

            // 富文本与特殊格式
            ['key' => self::RICH_TEXT->value, 'value' => '富文本编辑器'],
            ['key' => self::MARKDOWN->value, 'value' => 'Markdown编辑器'],
            ['key' => self::JSON->value, 'value' => 'JSON编辑器'],
            ['key' => self::CODE->value, 'value' => '代码编辑器'],
            ['key' => self::COLOR->value, 'value' => '颜色选择器'],
            ['key' => self::DATE->value, 'value' => '日期选择器'],
            ['key' => self::DATETIME->value, 'value' => '日期时间选择器'],
            ['key' => self::TIME->value, 'value' => '时间选择器'],

            // 结构化数据
            ['key' => self::KEY_VALUE->value, 'value' => '键值对配置'],
            ['key' => self::TABLE->value, 'value' => '表格配置'],
            ['key' => self::TREE->value, 'value' => '树形结构配置'],
        ];
    }


    /**
     * 生成前端验证规则（与后端validateValue逻辑对应）
     * @param int $type 配置类型枚举值
     * @return array 格式：[{label: 规则名, value: 规则值, message: 提示信息}, ...]
     */
    public static function getVueRules(int $type): array
    {
        return match ($type) {
            // 基础输入类规则
            self::TEXT->value, self::TEXTAREA->value => [
                ['label' => 'type', 'value' => 'string', 'message' => '请输入文本内容']
            ],
            self::PASSWORD->value => [
                ['label' => 'type', 'value' => 'string', 'message' => '请输入密码'],
                ['label' => 'min', 'value' => 6, 'message' => '密码长度不能少于6位']
            ],
            self::NUMBER->value => [
                ['label' => 'type', 'value' => 'number', 'message' => '请输入数字']
            ],

            // 格式验证类规则（使用统一正则常量）
            self::URL->value => [
                ['label' => 'type', 'value' => 'url', 'message' => '请输入正确的网址（如：https://example.com）']
            ],
            self::EMAIL->value => [
                ['label' => 'type', 'value' => 'email', 'message' => '请输入正确的邮箱地址（如：xxx@example.com）']
            ],
            self::PHONE->value => [
                ['label' => 'pattern', 'value' => self::REGEX_PHONE, 'message' => '请输入正确的手机号（11位数字）']
            ],

            // 选择类规则
            self::SELECT->value, self::RADIO->value => [
                ['label' => 'required', 'value' => 'true', 'message' => '请选择选项'],
                ['label' => 'type', 'value' => 'number', 'message' => '选择值格式错误']
            ],
            self::CHECKBOX->value, self::MULTI_SELECT->value, self::CASCADER->value => [
                ['label' => 'type', 'value' => 'array', 'message' => '选择值必须为数组格式']
            ],
            self::SWITCH->value => [
                ['label' => 'type', 'value' => 'boolean', 'message' => '请选择开关状态']
            ],

            // 特殊格式类规则（使用统一正则常量）
            self::COLOR->value => [
                ['label' => 'pattern', 'value' => self::REGEX_COLOR, 'message' => '请输入正确的颜色值（如：#FF0000）']
            ],
            self::DATE->value => [
                ['label' => 'pattern', 'value' => self::REGEX_DATE, 'message' => '请输入正确的日期格式（YYYY-MM-DD）']
            ],
            self::DATETIME->value => [
                ['label' => 'pattern', 'value' => self::REGEX_DATETIME, 'message' => '请输入正确的日期时间格式（YYYY-MM-DD HH:MM:SS）']
            ],
            self::TIME->value => [
                ['label' => 'pattern', 'value' => self::REGEX_TIME, 'message' => '请输入正确的时间格式（HH:MM:SS）']
            ],
            self::JSON->value => [
                ['label' => 'validator', 'value' => 'isJson', 'message' => '请输入正确的JSON格式']
            ],

            // 其他类型默认规则（无特殊验证需求）
            default => []
        };
    }


    /**
     * 验证配置值是否符合当前类型的格式要求（与前端getRules逻辑对应）
     * @param int $type 配置类型枚举值
     * @param mixed $value 待验证的值
     * @return bool 验证通过返回true，否则false
     */
    public static function validateValue(int $type, mixed $value,bool $allow=true): bool
    {
        $case = self::tryFrom($type);
        if (!$case) {
            return true; // 未知类型不验证
        }

        if ($allow&& empty($value)){
            return true;
        }
        return match ($case) {
            // 1. 基础文本类（对应前端string类型验证）
            self::TEXT, self::PASSWORD, self::TEXTAREA, self::RICH_TEXT, self::MARKDOWN, self::CODE =>
             is_string($value) || is_numeric($value) || is_null($value),

            // 2. 数字类（对应前端number类型验证）
            self::NUMBER =>
                is_numeric($value) || (is_string($value) && ctype_digit($value)),

            // 3. 格式验证类（使用与前端相同的正则/过滤器）
            self::URL => filter_var($value, FILTER_VALIDATE_URL) !== false,
            self::EMAIL => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            self::PHONE => is_string($value) && preg_match(self::REGEX_PHONE, $value),

            // 4. 选择类（对应前端array/number类型验证）
            self::SWITCH => self::validateSwitchValue($value),
            self::RADIO, self::SELECT => !is_array($value), // 单选值不能是数组（对应前端number类型）
            self::CHECKBOX, self::MULTI_SELECT, self::CASCADER =>
                is_array($value) || is_null($value), // 多选值必须是数组（对应前端array类型）

            // 5. 媒体类（单文件字符串/多文件数组）
            self::IMAGE, self::VIDEO, self::FILE => is_string($value), // 单媒体（字符串路径）
            self::IMAGES, self::FILES => is_array($value) || is_null($value), // 多媒体（数组路径）

            // 6. 特殊格式类（使用与前端相同的正则/逻辑）
            self::JSON => self::isValidJson($value),
            self::COLOR => is_string($value) && preg_match(self::REGEX_COLOR, $value),
            self::DATE => is_string($value) && preg_match(self::REGEX_DATE, $value),
            self::DATETIME => is_string($value) && preg_match(self::REGEX_DATETIME, $value),
            self::TIME => is_string($value) && preg_match(self::REGEX_TIME, $value),

            // 7. 结构化数据类（数组或JSON）
            self::KEY_VALUE, self::TABLE, self::TREE =>
                is_array($value) || self::isValidJson($value),
        };
    }


    /**
     * 专用方法：验证开关值是否符合YesOrNo枚举（与前端boolean类型验证对应）
     * @param mixed $value 待验证的值
     * @return bool 验证通过返回true
     */
    private static function validateSwitchValue(mixed $value): bool
    {
        // 转换布尔值为对应枚举值（对应前端boolean类型）
        if (is_bool($value)) {
            $value = $value ? YesOrNo::Yes->value : YesOrNo::No->value;
        }

        // 验证值是否为YesOrNo的合法值
        return YesOrNo::isValidValue((int)$value);
    }


    /**
     * 辅助方法：验证字符串是否为合法JSON（与前端isJson验证器对应）
     * @param mixed $value 待验证的值
     * @return bool 合法返回true，否则false
     */
    private static function isValidJson(mixed $value): bool
    {
        return is_string($value) && json_validate($value);
    }
}