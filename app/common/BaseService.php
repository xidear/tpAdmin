<?php

namespace app\common;

use enum\config\Code;
use app\common\trait\BaseTrait;
use think\App;
use think\db\exception\DbException;
use think\facade\Log;

class BaseService
{
    use BaseTrait;

    /**
     * 应用实例
     * @var App
     */
    protected App $app;

    /**
     * 构造方法
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->initialize();
    }

    /**
     * 初始化方法
     */
    protected function initialize()
    {
        // 子类可重写
    }

    /**
     * 记录信息日志
     * @param string $message
     * @param array $context
     * @param string $channel
     */
    protected function logInfo(string $message, array $context = [], string $channel = 'service'): void
    {
        Log::channel($channel)->info($message, $context);
    }

    /**
     * 记录调试日志
     * @param string $message
     * @param array $context
     * @param string $channel
     */
    protected function logDebug(string $message, array $context = [], string $channel = 'service'): void
    {
        if (env('app_debug', false)) {
            Log::channel($channel)->debug($message, $context);
        }
    }


    /**
     * 数据库事务处理
     * @param callable $callback
     * @return mixed
     * @throws \Exception
     */
    protected function transaction(callable $callback): mixed
    {
        try {
            return $this->app->db->transaction($callback);
        } catch (DbException $e) {
            $this->reportError(
                '数据库事务失败',
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ],
                Code::DB_ERROR->value
            );
            throw $e;
        } catch (\Exception $e) {
            $this->reportError(
                '事务执行失败',
                ['message' => $e->getMessage()],
                Code::ERROR->value
            );
            throw $e;
        }
    }

    /**
     * 获取容器实例
     * @param string $name
     * @param array $args
     * @return mixed
     */
    protected function make(string $name, array $args = []): mixed
    {
        return $this->app->make($name, $args);
    }

    /**
     * 生成标准结果
     * @param bool $success
     * @param mixed|null $data
     * @param string $message
     * @param int $code
     * @return array
     */
    protected function result(bool $success, mixed $data = null, string $message = '', int $code = 0): array
    {
        return [
            'success' => $success,
            'data' => $data,
            'message' => $message ?: $this->msg,
            'code' => $code ?: $this->code
        ];
    }

    /**
     * 成功结果
     * @param mixed|null $data
     * @param string $message
     * @param int $code
     * @return array
     */
    protected function success(mixed $data = null, string $message = '操作成功', int $code = Code::SUCCESS->value): array
    {
        $this->true($message, $code);
        return $this->result(true, $data, $message, $code);
    }

    /**
     * 失败结果
     * @param string $message
     * @param mixed|null $data
     * @param int $code
     * @return array
     */
    protected function error(string $message = '操作失败', mixed $data = null, int $code = Code::ERROR->value): array
    {
        $this->false($message, $code);
        return $this->result(false, $data, $message, $code);
    }
}