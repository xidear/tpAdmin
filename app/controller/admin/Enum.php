<?php

namespace app\controller\admin;

use app\common\BaseController;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use think\App;
use think\exception\ValidateException;

class Enum extends BaseController
{
    // 免权限验证（移除权限中间件）
    protected $middleware = [];

    // 枚举类所在目录（基于项目根目录）
    protected string $enumDir = "";

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->enumDir = app_path() . 'common/enum/';
    }

    /**
     * 获取所有可用的枚举名称列表（对应路由index方法）
     * @return \think\Response
     */
    public function index()
    {
        // 自动扫描枚举目录，获取所有枚举类名和路径信息
        $enumList = $this->scanEnumClasses();
        return $this->success($enumList);
    }

    /**
     * 获取指定枚举的列表数据（对应路由read方法）
     * @param string $enum_code 枚举类名（如FileStatus、AdminStatus等）
     * @return \think\Response
     */
    public function getEnum(string $enum_code)
    {
        // 1. 验证枚举类是否存在
        if (empty($enum_code)) {
            return $this->error('枚举名称不能为空');
        }

        // 2. 自动扫描枚举类，验证合法性（避免手动维护白名单）
        $validEnums = $this->scanEnumClasses();
        $enumInfo = null;

        // 查找匹配的枚举信息
        foreach ($validEnums as $enum) {
            if ($enum['className'] === $enum_code) {
                $enumInfo = $enum;
                break;
            }
        }

        if (!$enumInfo) {
            return $this->error("不支持的枚举类型：{$enum_code}");
        }

        // 3. 构建枚举类完整命名空间
        $className = $enumInfo['fullClassName'];
        if (!class_exists($className)) {
            return $this->error("枚举类文件存在，但类定义不存在：{$className}");
        }

        // 4. 验证枚举类是否使用了EnumTrait（确保有getList方法）
        $reflection = new ReflectionClass($className);
        if (!$reflection->hasMethod('getList')) {
            return $this->error("枚举类{$enum_code}未实现getList()方法（需使用EnumTrait）");
        }

        // 5. 调用枚举类的getList()方法，返回标准化数据
        try {
            $enumData = $className::getList();
            // 转换为前端通用的label-value结构（兼容现有EnumTrait的key-value输出）
            $result = array_map(function ($item) {
                return [
                    'label' => $item['value'] ?? '',
                    'value' => $item['key'] ?? ''
                ];
            }, $enumData);
        } catch (\Throwable $e) {
            return $this->error("枚举数据获取失败：{$e->getMessage()}");
        }

        return $this->success($result);
    }

    /**
     * 自动扫描枚举目录，返回所有有效的枚举类信息
     * @return array 枚举信息列表，包含类名、完整命名空间、文件路径等
     */
    protected function scanEnumClasses(): array
    {
        $enumClasses = [];

        // 检查枚举目录是否存在
        if (!is_dir($this->enumDir)) {
            return $enumClasses;
        }

        // 递归扫描目录下的所有PHP文件
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->enumDir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            // 只处理PHP文件
            if ($file->isFile() && $file->getExtension() === 'php') {
                // 获取相对路径（相对于枚举根目录）
                $relativePath = str_replace($this->enumDir, '', $file->getPathname());
                $relativePath = str_replace('\\', '/', $relativePath);

                // 获取文件名（不含扩展名）作为类名
                $fileName = $file->getBasename('.php');

                // 构建完整的命名空间
                $namespacePath = str_replace('/', '\\', dirname($relativePath));
                if ($namespacePath === '.') {
                    $namespacePath = '';
                }

                $fullClassName = "app\\common\\enum\\" . ($namespacePath ? $namespacePath . '\\' : '') . $fileName;

                // 验证文件中是否存在对应的枚举类
                if (class_exists($fullClassName)) {
                    // 额外验证：是否为枚举类
                    $reflection = new ReflectionClass($fullClassName);
                    if ($reflection->isEnum()) {
                        $enumClasses[] = [
                            'className' => $fileName,           // 类名（如FileStatus）
                            'fullClassName' => $fullClassName, // 完整命名空间（如app\common\enum\file\FileStatus）
                            'filePath' => $relativePath,       // 相对文件路径（如file/FileStatus.php）
                            'namespace' => $namespacePath,     // 子命名空间（如file）
                            'displayName' => $this->getEnumDisplayName($fileName, $namespacePath) // 显示名称
                        ];
                    }
                }
            }
        }

        // 按命名空间分组并排序
        usort($enumClasses, function ($a, $b) {
            // 先按命名空间排序
            if ($a['namespace'] !== $b['namespace']) {
                if ($a['namespace'] === '') return -1;
                if ($b['namespace'] === '') return 1;
                return strcmp($a['namespace'], $b['namespace']);
            }
            // 再按类名排序
            return strcmp($a['className'], $b['className']);
        });

        return $enumClasses;
    }

    /**
     * 获取枚举的显示名称
     * @param string $className 类名
     * @param string $namespace 命名空间
     * @return string 显示名称
     */
    protected function getEnumDisplayName(string $className, string $namespace): string
    {
        // 移除常见的后缀
        $displayName = str_replace(['Enum', 'Type', 'Status', 'Permission'], '', $className);

        // 如果命名空间不为空，添加前缀
        if ($namespace) {
            $namespaceDisplay = ucfirst($namespace);
            $displayName = $namespaceDisplay . ' - ' . $displayName;
        }

        return $displayName ?: $className;
    }
}
