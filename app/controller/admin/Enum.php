<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\common\service\EnumService;
use think\App;
use think\Response;

class Enum extends BaseController
{
    // 免权限验证（移除权限中间件）
    protected $middleware = [];

    protected EnumService $enumService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->enumService = new EnumService($app);
    }

    /**
     * 获取所有可用的枚举名称列表（对应路由index方法）
     * @return Response
     */
    public function index(): Response
    {
        try {
            $enumList = $this->enumService->getEnumList();
            return $this->success($enumList);
        } catch (\Exception $e) {
            return $this->error('获取枚举列表失败：' . $e->getMessage());
        }
    }

    /**
     * 获取指定枚举的列表数据（对应路由read方法）
     * @param string $enum_code 枚举类名（如FileStatus、AdminStatus等）
     * @return Response
     */
    public function getEnum(string $enum_code): Response
    {
        try {
            $result = $this->enumService->getEnumData($enum_code);
            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取指定命名空间下的所有枚举
     * @param string $namespace 命名空间（如file、admin等）
     * @return Response
     */
    public function getEnumsByNamespace(string $namespace): Response
    {
        try {
            $enums = $this->enumService->getEnumsByNamespace($namespace);
            return $this->success($enums);
        } catch (\Exception $e) {
            return $this->error('获取枚举失败：' . $e->getMessage());
        }
    }

    /**
     * 检查枚举类是否存在
     * @param string $enum_code
     * @return Response
     */
    public function checkEnum(string $enum_code): Response
    {
        try {
            $exists = $this->enumService->enumExists($enum_code);
            return $this->success(['exists' => $exists]);
        } catch (\Exception $e) {
            return $this->error('检查枚举失败：' . $e->getMessage());
        }
    }
}
