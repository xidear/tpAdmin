<?php

namespace app\common\service;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use think\App;

class EnumService
{
    // 枚举类所在目录（基于项目根目录）
    protected string $enumDir = "";

    public function __construct(App $app)
    {
        $this->enumDir = app_path() . 'common/enum/';
    }

    /**
     * 获取所有可用的枚举名称列表
     * @return array
     */
    public function getEnumList(): array
    {
        return $this->scanEnumClasses();
    }

    /**
     * 获取指定枚举的列表数据
     * @param string $enumCode 枚举类名（如FileStatus、AdminStatus等）
     * @return array
     * @throws \Exception
     */
    public function getEnumData(string $enumCode): array
    {
        // 1. 验证枚举类是否存在
        if (empty($enumCode)) {
            throw new \Exception('枚举名称不能为空');
        }

        // 2. 自动扫描枚举类，验证合法性
        $validEnums = $this->scanEnumClasses();
        $enumInfo = null;

        // 查找匹配的枚举信息
        foreach ($validEnums as $enum) {
            if ($enum['className'] === $enumCode) {
                $enumInfo = $enum;
                break;
            }
        }

        if (!$enumInfo) {
            throw new \Exception("不支持的枚举类型：{$enumCode}");
        }

        // 3. 构建枚举类完整命名空间
        $className = $enumInfo['fullClassName'];
        if (!class_exists($className)) {
            throw new \Exception("枚举类文件存在，但类定义不存在：{$className}");
        }

        // 4. 验证枚举类是否使用了EnumTrait（确保有getList方法）
        $reflection = new ReflectionClass($className);
        if (!$reflection->hasMethod('getList')) {
            throw new \Exception("枚举类{$enumCode}未实现getList()方法（需使用EnumTrait）");
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
            throw new \Exception("枚举数据获取失败：{$e->getMessage()}");
        }

        return $result;
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
                // 跨平台路径处理：Windows使用\，Linux使用/，需要统一处理
                // 使用realpath获取标准化的绝对路径，自动解决路径分隔符问题
                $enumDirReal = realpath($this->enumDir);
                $filePathReal = realpath($file->getPathname());
                
                // 如果realpath失败，回退到手动规范化（适用于符号链接等特殊情况）
                if ($enumDirReal === false || $filePathReal === false) {
                    // 手动统一路径分隔符为当前系统的标准分隔符
                    $enumDirNormalized = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->enumDir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                    $filePathNormalized = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $file->getPathname());
                    $relativePath = str_replace($enumDirNormalized, '', $filePathNormalized);
                } else {
                    // 使用realpath结果计算相对路径（最可靠的方法）
                    $relativePath = str_replace($enumDirReal . DIRECTORY_SEPARATOR, '', $filePathReal);
                }
                
                // 统一转换为正斜杠，因为PHP命名空间使用反斜杠，而文件路径用正斜杠便于处理
                $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

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

    /**
     * 检查枚举类是否存在
     * @param string $enumCode
     * @return bool
     */
    public function enumExists(string $enumCode): bool
    {
        try {
            $validEnums = $this->scanEnumClasses();
            foreach ($validEnums as $enum) {
                if ($enum['className'] === $enumCode) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取指定命名空间下的所有枚举
     * @param string $namespace 命名空间（如file、admin等）
     * @return array
     */
    public function getEnumsByNamespace(string $namespace): array
    {
        $allEnums = $this->scanEnumClasses();
        $namespaceEnums = [];

        foreach ($allEnums as $enum) {
            if ($enum['namespace'] === $namespace) {
                $namespaceEnums[] = $enum;
            }
        }

        return $namespaceEnums;
    }
}
