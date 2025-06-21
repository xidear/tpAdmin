<?php

namespace app\common\trait;

use app\common\enum\Code;
use think\Collection;
use think\contract\Arrayable;

trait BaseTrait
{
    public string $msg = "";
    public int $code = \app\common\enum\Code::SUCCESS->value;

    /**返回错误信息*/
    public function getError(): string
    {
        return $this->msg;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    /**返回提示信息*/
    public function getMessage(): string
    {
        return $this->msg;
    }

    /**返回正确
     * @param string $msg
     * @param int $code
     * @return bool
     */
    public function true(string $msg = "成功", int $code = Code::SUCCESS->value): bool
    {
        $this->msg = $msg;
        $this->code = $code;
        return true;
    }


    /**返回错误
     * @param string $msg
     * @param int $code
     * @return bool
     */
    public function false(string $msg = "发生错误", int $code = Code::ERROR->value): bool
    {
        $this->msg = $msg;
        $this->code = $code;
        return false;
    }

    /**
     * 给管理员上报错误
     * @param string $title
     * @param Arrayable|array $content
     * @param int $code
     * @return bool
     */
    public function reportError(string $title, Arrayable|array $content = [],int $code=Code::ERROR->value):bool
    {

        \think\facade\Log::error("API Error [$code]: $title",(array) $content);

        return  true;
//        $msgModel = new Msg();
//        return $msgModel->sendMsg(
//            senderId: 0,
//            receiverIds: [config('admin.super_id')],
//            msgTitle: $title,
//            content: $content
//        );


    }




}