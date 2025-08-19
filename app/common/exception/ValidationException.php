<?php

namespace app\common\exception;

use app\common\BaseException;
use app\common\enum\task\Code;
use think\Exception;

class ValidationException extends BaseException
{
    public $code = 400;

    public function __construct(string $message = '', int $code =  Code::ERROR->value, \Exception $previous = null, array $headers = [], $statusCode = Code::SUCCESS->value)
    {
        parent::__construct($message, $code, $previous, $headers, $statusCode);
    }
}
