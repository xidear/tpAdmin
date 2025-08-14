<?php
namespace app\common\trait;

trait TreeTrait
{
    /**
     * 树形结构配置
     * @var array
     */
    protected $treeConfig = [
        'parentKey' => 'parent_id',     // 父级ID字段名
        'primaryKey' => 'id',           // 主键字段名
        'pathKey' => 'path',            // 路径字段名
        'levelKey' => 'level',          // 层级字段名
        'nameKey' => 'name',            // 名称字段名
        'childrenKey' => 'children',    // 子节点键名
        'pathSeparator' => ',',         // 路径分隔符
    ];

    /**
     * 设置树形结构配置
     * @param array $config
     * @return $this
     */
    public function setTreeConfig(array $config)
    {
        $this->treeConfig = array_merge($this->treeConfig, $config);
        return $this;
    }

    /**
     * 获取树形结构配置
     * @return array
     */
    public function getTreeConfig(): array
    {
        return $this->treeConfig;
    }

    /**
     * 获取指定节点的所有后代ID（包括自己）
     * @param int $nodeId
     * @return array
     */
    public function getAllDescendantIds(int $nodeId): array
    {
        $config = $this->getTreeConfig();
        $ids = [];
        $this->collectChildIds($nodeId, $ids);
        return $ids;
    }

    /**
     * 递归收集子节点ID
     * @param int $parentId
     * @param array &$ids
     */
    protected function collectChildIds(int $parentId, array &$ids): void
    {
        $config = $this->getTreeConfig();
        $ids[] = $parentId;
        
        $children = $this->where($config['parentKey'], $parentId)->select();
        foreach ($children as $child) {
            $childId = $child[$config['primaryKey']];
            $this->collectChildIds($childId, $ids);
        }
    }

    /**
     * 获取指定节点的所有祖先ID（不包括自己）
     * @param int $nodeId
     * @return array
     */
    public function getAllAncestorIds(int $nodeId): array
    {
        $config = $this->getTreeConfig();
        $ancestorIds = [];
        
        $node = $this->find($nodeId);
        if (!$node) {
            return $ancestorIds;
        }
        
        $parentId = $node[$config['parentKey']];
        while ($parentId > 0) {
            $ancestorIds[] = $parentId;
            $parent = $this->find($parentId);
            if (!$parent) {
                break;
            }
            $parentId = $parent[$config['parentKey']];
        }
        
        return array_reverse($ancestorIds);
    }

    /**
     * 根据父ID获取树形结构
     * @param int $parentId
     * @param array $fields
     * @return array
     */
    public function getTreeByParentId(int $parentId, array $fields = ['*']): array
    {
        $config = $this->getTreeConfig();
        $nodes = $this->where($config['parentKey'], $parentId)
            ->field($fields)
            ->order($config['primaryKey'], 'asc')
            ->select()
            ->toArray();
        
        return $this->buildTree($nodes, $parentId);
    }

    /**
     * 构建树形结构
     * @param array $nodes
     * @param int $parentId
     * @return array
     */
    protected function buildTree(array $nodes, int $parentId): array
    {
        $config = $this->getTreeConfig();
        $tree = [];
        
        foreach ($nodes as $node) {
            if ($node[$config['parentKey']] == $parentId) {
                $children = $this->buildTree($nodes, $node[$config['primaryKey']]);
                if ($children) {
                    $node[$config['childrenKey']] = $children;
                }
                $tree[] = $node;
            }
        }
        
        return $tree;
    }

    /**
     * 获取完整的树形结构
     * @param array $fields
     * @return array
     */
    public function getAllTree(array $fields = ['*']): array
    {
        $config = $this->getTreeConfig();
        $nodes = $this->field($fields)
            ->order($config['primaryKey'], 'asc')
            ->select()
            ->toArray();
        
        return $this->buildTree($nodes, 0);
    }

    /**
     * 更新节点路径
     * @param int $nodeId
     * @return bool
     */
    public function updateNodePath(int $nodeId): bool
    {
        $config = $this->getTreeConfig();
        $node = $this->find($nodeId);
        if (!$node) {
            return false;
        }
        
        // 构建路径
        $path = [];
        $parentId = $node[$config['parentKey']];
        
        if ($parentId == 0) {
            // 根节点
            $path = [$nodeId];
            $level = 1;
        } else {
            // 获取父节点路径
            $parent = $this->find($parentId);
            if (!$parent) {
                return false;
            }
            
            $parentPath = explode($config['pathSeparator'], $parent[$config['pathKey']]);
            $path = array_merge($parentPath, [$nodeId]);
            $level = count($path);
        }
        
        // 更新当前节点
        $updateData = [
            $config['pathKey'] => implode($config['pathSeparator'], $path),
            $config['levelKey'] => $level
        ];
        
        $this->where($config['primaryKey'], $nodeId)->update($updateData);
        
        // 递归更新子节点
        $this->updateChildrenPaths($nodeId, $path);
        
        return true;
    }

    /**
     * 递归更新子节点路径
     * @param int $parentId
     * @param array $parentPath
     */
    protected function updateChildrenPaths(int $parentId, array $parentPath): void
    {
        $config = $this->getTreeConfig();
        $children = $this->where($config['parentKey'], $parentId)->select();
        
        foreach ($children as $child) {
            $childId = $child[$config['primaryKey']];
            $childPath = array_merge($parentPath, [$childId]);
            $level = count($childPath);
            
            $updateData = [
                $config['pathKey'] => implode($config['pathSeparator'], $childPath),
                $config['levelKey'] => $level
            ];
            
            $this->where($config['primaryKey'], $childId)->update($updateData);
            
            // 递归更新子节点
            $this->updateChildrenPaths($childId, $childPath);
        }
    }

    /**
     * 检查是否为祖先节点
     * @param int $ancestorId
     * @param int $descendantId
     * @return bool
     */
    public function isAncestor(int $ancestorId, int $descendantId): bool
    {
        $config = $this->getTreeConfig();
        $descendant = $this->find($descendantId);
        if (!$descendant) {
            return false;
        }
        
        $path = explode($config['pathSeparator'], $descendant[$config['pathKey']]);
        return in_array($ancestorId, $path);
    }

    /**
     * 检查是否为后代节点
     * @param int $descendantId
     * @param int $ancestorId
     * @return bool
     */
    public function isDescendant(int $descendantId, int $ancestorId): bool
    {
        return $this->isAncestor($ancestorId, $descendantId);
    }

    /**
     * 检查是否为兄弟节点
     * @param int $nodeId1
     * @param int $nodeId2
     * @return bool
     */
    public function isSibling(int $nodeId1, int $nodeId2): bool
    {
        $config = $this->getTreeConfig;
        $node1 = $this->find($nodeId1);
        $node2 = $this->find($nodeId2);
        
        if (!$node1 || !$node2) {
            return false;
        }
        
        return $node1[$config['parentKey']] == $node2[$config['parentKey']];
    }
}