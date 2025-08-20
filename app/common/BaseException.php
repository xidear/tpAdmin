<?php
declare(strict_types=1);

namespace app\common;

use app\common\enum\Code;
use Exception;
use think\exception\HttpException;

abstract class BaseException extends HttpException
{
    protected const int HTTP_SUCCESS = Code::SUCCESS->value;

    public function __construct(string $message = '', int $code = 0, Exception $previous = null, array $headers = [], $statusCode =Code::SUCCESS->value)
    {
        parent::__construct($statusCode, $message ?: $this->getMessage(), $previous, $headers, $code);
    }

    public function getStatusCode(): int
    {
        return self::HTTP_SUCCESS;
    }


}
