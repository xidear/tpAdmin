<?php

namespace app\common\trait;

use app\common\enum\Code;
use think\exception\ValidateException;
use think\Response;

trait ReturnTrait
{
    /**
     * 统一响应格式
     */
    protected function response(
        int    $code = Code::SUCCESS->value,
        string $message = '',
        mixed  $data = null,
        int    $httpStatus = Code::SUCCESS->value
    ): Response
    {
        $response = [
            'code' => $code,
            'timestamp' => time(),
            'msg' => $message ?: $this->getMessageByCode($code),
            'data' => $data
        ];

        // 记录非200响应
        if ($code !== Code::SUCCESS->value) {
            $this->reportError($message,$response,$code);
        }

        return json($response)->code($httpStatus);
    }

    /**
     * 成功响应快捷方式
     */
    protected function success($data = null, string $message = '操作成功'): Response
    {
        return $this->response(Code::SUCCESS->value, $message, $data);
    }

    /**
     * 错误响应快捷方式
     */
    protected function error(
        string $message = '操作失败',
        int    $code = Code::ERROR->value,
        mixed  $data = null
    ): Response
    {
        return $this->response($code, $message, $data);
    }

    /**
     * HTTP状态码映射
     */
    private function getMessageByCode(int $code): string
    {
        return Code::getValue($code);
    }

//    // 增强的验证方法
//    protected function validateWithResponse(
//        array        $data,
//        string|array $validate,
//        array        $message = [],
//        bool         $batch = false
//    )
//    {
//        try {
//            parent::validate($data, $validate, $message, $batch);
//        } catch (ValidateException $e) {
//
//            return   $this->error($e->getError());
//        }
//    }
}