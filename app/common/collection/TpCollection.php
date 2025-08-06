<?php
// app/common/collection/TpCollection.php
namespace app\common\collection;

use think\model\Collection;
use think\facade\Cache;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class TpCollection extends Collection
{


    /**
     * 默认需要隐藏的字段
     * @var array
     */
    protected $defaultHidden = ['delete_time', 'password', 'salt'];

    /**
     * 转换为树形结构
     * @param int $pid 父级ID，默认为0
     * @param string $parentPk 父ID字段名，默认为'parent_id'
     * @param string $childrenKey 子节点键名，默认为'children'
     * @param int|null $maxLevel 最大层级限制
     * @return array
     */
    public function toTree(
        int $pid = 0,
        string $parentPk = 'parent_id',
        string $childrenKey = 'children',
        ?int $maxLevel = null
    ): array {
        if ($this->isEmpty()) {
            return [];
        }

        // 获取模型主键（如id）
        $pk = $this->first()->getPk();
        // 转换为数组（先处理隐藏字段）
        $data = $this->toArray();

        return $this->array2Tree(
            data: $data,
            pid: $pid,
            pk: $pk,
            parentPk: $parentPk,
            childrenKey: $childrenKey,
            level: 0,
            maxLevel: $maxLevel
        );
    }

    /**
     * 递归构建树形结构
     */
    protected function array2Tree(
        array $data,
        int $pid,
        string $pk,
        string $parentPk,
        string $childrenKey,
        int $level,
        ?int $maxLevel = null
    ): array {
        $tree = [];

        // 超过最大层级则终止
        if ($maxLevel !== null && $level > $maxLevel) {
            return $tree;
        }

        foreach ($data as $item) {
            // 处理对象类型元素
            if (is_object($item)) {
                $item = (array)$item;
            }

            if (!is_array($item) || !isset($item[$pk], $item[$parentPk])) {
                continue;
            }

            // 匹配当前父节点
            if ($item[$parentPk] == $pid) {
                // 递归查询子节点（层级+1）
                $canRecurse = $maxLevel === null || $level < $maxLevel;
                $children = $canRecurse ? $this->array2Tree(
                    data: $data,
                    pid: $item[$pk],
                    pk: $pk,
                    parentPk: $parentPk,
                    childrenKey: $childrenKey,
                    level: $level + 1,
                    maxLevel: $maxLevel
                ) : [];

                if (!empty($children)) {
                    $item[$childrenKey] = $children;
                }

                // 层级信息
                $item['level'] = $level;

                $tree[] = $item;
            }
        }

        return $tree;
    }

    /**
     * 缓存当前集合数据
     * @param string $key 缓存键名
     * @param int $ttl 过期时间（秒），0为永久
     * @param string $store 缓存驱动
     * @return bool
     */
    public function cache(string $key, int $ttl = 3600, string $store = 'default'): bool
    {
        return Cache::store($store)->set($key, $this->items, $ttl);
    }

    /**
     * 获取所有子级ID（包含多级子节点）
     * @param array $parentIds 父节点ID数组
     * @param string $parentPk 父ID字段名
     * @param string $idField ID字段名
     * @return array
     */
    public function getAllChildrenIds(
        array $parentIds,
        string $parentPk = 'parent_id',
        string $idField = 'id'
    ): array {
        $parentIds = array_map('intval', $parentIds);
        $children = $this->whereIn($parentPk, $parentIds)->column($idField);

        if (empty($children)) {
            return [];
        }

        // 递归获取所有子级
        return array_merge($children, $this->getAllChildrenIds($children, $parentPk, $idField));
    }

    /**
     * 拼接字段值
     * @param string $field 字段名
     * @param string $separator 分隔符
     * @return string
     */
    public function implode(string $field, string $separator = ','): string
    {
        return implode($separator, $this->column($field));
    }

    /**
     * 导出为Excel
     * @param array $headers 表头 ['字段名' => '显示文本']
     * @param string $filename 文件名
     * @param string $type 文件类型 xlsx/csv
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function simple_export(array $headers, string $filename = 'export', string $type = 'xlsx')
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 设置表头
        $colIndex = 1;
        $fieldMap = array_keys($headers);
        foreach ($headers as $label) {
            $sheet->setCellValueByColumnAndRow($colIndex++, 1, $label);
        }

        // 填充数据
        $rowIndex = 2;
        foreach ($this->items as $item) {
            $colIndex = 1;
            $item = is_object($item) ? $item->toArray() : $item;
            foreach ($fieldMap as $field) {
                $sheet->setCellValueByColumnAndRow($colIndex++, $rowIndex, $item[$field] ?? '');
            }
            $rowIndex++;
        }

        // 输出文件
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=\"{$filename}.{$type}\"");
        header('Cache-Control: max-age=0');

        $writer = $type === 'csv' ? new Csv($spreadsheet) : new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * 隐藏指定字段（支持合并默认隐藏字段）
     * @param array $fields 要隐藏的字段
     * @param bool $merge 是否合并默认隐藏字段
     * @return $this
     */
    public function hidden(array $fields = [], bool $merge = true): self
    {
        $hiddenFields = $merge ? array_merge($this->defaultHidden, $fields) : $fields;
        $hiddenFields = array_unique($hiddenFields);

        foreach ($this->items as &$item) {
            $item = is_object($item) ? $item->toArray() : $item;
            foreach ($hiddenFields as $field) {
                if (isset($item[$field])) {
                    unset($item[$field]);
                }
            }
        }

        return $this;
    }
}