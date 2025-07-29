<?php

namespace app\common\enum;

use app\common\trait\EnumTrait;

enum Code: int
{

    use EnumTrait;

    case WARNING = 100;
    case SUCCESS = 200;
    case CREATED = 201;
    case NO_CONTENT = 204;
    case NEED_CHANGE_PASSWORD = 503;
    case REQUEST_ERROR = 400;
    case ERROR = 500;
    case FORM_TOKEN_ERROR = 501;
    case NOT_FOUND = 404;
    case TOKEN_INVALID = 401;
    case FORBIDDEN = 403;


    case DB_ERROR = 99;

    public static function getList(): array
    {
        return [
            [
                'key' => self::SUCCESS->value,
                'value' => "操作成功",
            ],
            [
                'key' => self::CREATED->value,
                'value' => "创建成功",
            ],

            [
                'key' => self::NO_CONTENT->value,
                'value' => "无内容",
            ],

            [
                'key' => self::REQUEST_ERROR->value,
                'value' => "请求参数错误",
            ],

            [
                'key' => self::TOKEN_INVALID->value,
                'value' => "未授权",
            ],

            [
                'key' => self::FORBIDDEN->value,
                'value' => "授权超时,请重新登录",
            ],

            [
                'key' => self::NOT_FOUND->value,
                'value' => "资源未找到",
            ],


            [
                'key' => self::ERROR->value,
                'value' => "系统错误",
            ],

            [
                'key' => self::DB_ERROR->value,
                'value' => "数据库错误",
            ],


        ];


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


        return "未知状态";
    }


}