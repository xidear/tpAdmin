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
        'sortKey' => 'sort',            // 排序字段名
        'statusKey' => 'status',        // 状态字段名
        'deletedAtKey' => 'deleted_at', // 软删除字段名
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
        $ids = [$nodeId];
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
        
        $children = $this->where($config['parentKey'], $parentId)->select();
        foreach ($children as $child) {
            $childId = $child[$config['primaryKey']];
            $ids[] = $childId;
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
     * 获取指定节点的所有祖先ID（不包括自己和0）
     * @param int $nodeId
     * @return array
     */
    public function getAllNonZeroAncestorIds(int $nodeId): array
    {
        $ancestorIds = $this->getAllAncestorIds($nodeId);
        return array_filter($ancestorIds, function($id) {
            return $id > 0;
        });
    }

    /**
     * 获取指定节点的所有祖先信息（包括名称等）
     * @param int $nodeId
     * @param array $fields 要获取的字段
     * @return array
     */
    public function getAllAncestors(int $nodeId, array $fields = ['*']): array
    {
        $ancestorIds = $this->getAllAncestorIds($nodeId);
        if (empty($ancestorIds)) {
            return [];
        }
        
        $config = $this->getTreeConfig();
        return $this->whereIn($config['primaryKey'], $ancestorIds)
            ->field($fields)
            ->order($config['levelKey'], 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 获取指定节点的所有兄弟节点ID（包括自己）
     * @param int $nodeId
     * @return array
     */
    public function getAllSiblingIds(int $nodeId): array
    {
        $config = $this->getTreeConfig();
        $node = $this->find($nodeId);
        if (!$node) {
            return [];
        }
        
        $parentId = $node[$config['parentKey']];
        return $this->where($config['parentKey'], $parentId)
            ->column($config['primaryKey']);
    }

    /**
     * 获取指定节点的所有兄弟节点（包括自己）
     * @param int $nodeId
     * @param array $fields 要获取的字段
     * @return array
     */
    public function getAllSiblings(int $nodeId, array $fields = ['*']): array
    {
        $config = $this->getTreeConfig();
        $node = $this->find($nodeId);
        if (!$node) {
            return [];
        }
        
        $parentId = $node[$config['parentKey']];
        return $this->where($config['parentKey'], $parentId)
            ->field($fields)
            ->order($config['sortKey'] ?? $config['primaryKey'], 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 获取指定节点的所有兄弟节点ID（不包括自己）
     * @param int $nodeId
     * @return array
     */
    public function getSiblingIds(int $nodeId): array
    {
        $siblingIds = $this->getAllSiblingIds($nodeId);
        return array_filter($siblingIds, function($id) use ($nodeId) {
            return $id != $nodeId;
        });
    }

    /**
     * 获取指定节点的所有叶子节点ID
     * @param int $nodeId
     * @return array
     */
    public function getLeafNodeIds(int $nodeId): array
    {
        $config = $this->getTreeConfig();
        $leafIds = [];
        $this->collectLeafIds($nodeId, $leafIds);
        return $leafIds;
    }

    /**
     * 递归收集叶子节点ID
     * @param int $parentId
     * @param array &$leafIds
     */
    protected function collectLeafIds(int $parentId, array &$leafIds): void
    {
        $config = $this->getTreeConfig();
        $children = $this->where($config['parentKey'], $parentId)->select();
        
        if (empty($children)) {
            $leafIds[] = $parentId;
            return;
        }
        
        foreach ($children as $child) {
            $childId = $child[$config['primaryKey']];
            $this->collectLeafIds($childId, $leafIds);
        }
    }

    /**
     * 获取指定节点的所有叶子节点
     * @param int $nodeId
     * @param array $fields 要获取的字段
     * @return array
     */
    public function getLeafNodes(int $nodeId, array $fields = ['*']): array
    {
        $leafIds = $this->getLeafNodeIds($nodeId);
        if (empty($leafIds)) {
            return [];
        }
        
        $config = $this->getTreeConfig();
        return $this->whereIn($config['primaryKey'], $leafIds)
            ->field($fields)
            ->select()
            ->toArray();
    }

    /**
     * 获取指定层级的节点
     * @param int $level
     * @param array $fields 要获取的字段
     * @param array $conditions 额外条件
     * @return array
     */
    public function getNodesByLevel(int $level, array $fields = ['*'], array $conditions = []): array
    {
        $config = $this->getTreeConfig();
        $query = $this->where($config['levelKey'], $level);
        
        if (!empty($conditions)) {
            $query->where($conditions);
        }
        
        if (isset($config['sortKey'])) {
            $query->order($config['sortKey'], 'asc');
        }
        
        return $query->field($fields)->select()->toArray();
    }

    /**
     * 获取指定节点的层级信息
     * @param int $nodeId
     * @return array
     */
    public function getNodeLevelInfo(int $nodeId): array
    {
        $config = $this->getTreeConfig();
        $node = $this->find($nodeId);
        if (!$node) {
            return [];
        }
        
        $level = $node[$config['levelKey']] ?? 1;
        $ancestors = $this->getAllAncestors($nodeId, [$config['primaryKey'], $config['nameKey'], $config['levelKey']]);
        
        return [
            'node_id' => $nodeId,
            'level' => $level,
            'ancestors' => $ancestors,
            'ancestor_count' => count($ancestors),
            'is_root' => $level == 1,
            'is_leaf' => !$this->hasChildren($nodeId)
        ];
    }

    /**
     * 获取树的统计信息
     * @param int $rootId 根节点ID，默认为0
     * @return array
     */
    public function getTreeStats(int $rootId = 0): array
    {
        $config = $this->getTreeConfig();
        $stats = [
            'total_nodes' => 0,
            'total_levels' => 0,
            'leaf_nodes' => 0,
            'internal_nodes' => 0,
            'max_children' => 0,
            'avg_children' => 0
        ];
        
        if ($rootId == 0) {
            $allNodes = $this->select()->toArray();
        } else {
            $allNodes = $this->getAllChildren($rootId);
        }
        
        if (empty($allNodes)) {
            return $stats;
        }
        
        $stats['total_nodes'] = count($allNodes);
        $stats['total_levels'] = max(array_column($allNodes, $config['levelKey']));
        
        $childrenCounts = [];
        foreach ($allNodes as $node) {
            $childrenCount = $this->getChildrenCount($node[$config['primaryKey']]);
            $childrenCounts[] = $childrenCount;
            
            if ($childrenCount == 0) {
                $stats['leaf_nodes']++;
            } else {
                $stats['internal_nodes']++;
            }
        }
        
        $stats['max_children'] = max($childrenCounts);
        $stats['avg_children'] = round(array_sum($childrenCounts) / count($childrenCounts), 2);
        
        return $stats;
    }

    /**
     * 批量更新节点路径
     * @param array $nodeIds
     * @return bool
     */
    public function batchUpdatePaths(array $nodeIds): bool
    {
        if (empty($nodeIds)) {
            return true;
        }
        
        $this->startTrans();
        try {
            foreach ($nodeIds as $nodeId) {
                $this->updateNodePath($nodeId);
            }
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            return false;
        }
    }

    /**
     * 批量移动节点
     * @param array $nodeIds
     * @param int $newParentId
     * @return bool
     */
    public function batchMoveNodes(array $nodeIds, int $newParentId): bool
    {
        if (empty($nodeIds)) {
            return true;
        }
        
        $config = $this->getTreeConfig();
        
        // 检查是否移动到自己的后代
        foreach ($nodeIds as $nodeId) {
            if ($this->isDescendant($newParentId, $nodeId)) {
                return false;
            }
        }
        
        $this->startTrans();
        try {
            // 批量更新父节点
            $this->whereIn($config['primaryKey'], $nodeIds)->update([
                $config['parentKey'] => $newParentId
            ]);
            
            // 批量更新路径
            foreach ($nodeIds as $nodeId) {
                $this->updateNodePath($nodeId);
            }
            
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            return false;
        }
    }

    /**
     * 获取节点的完整路径（包括名称）
     * @param int $nodeId
     * @param string $separator 分隔符
     * @return string
     */
    public function getFullPath(int $nodeId, string $separator = ' > '): string
    {
        $config = $this->getTreeConfig();
        $node = $this->find($nodeId);
        if (!$node) {
            return '';
        }
        
        $ancestors = $this->getAllAncestors($nodeId, [$config['nameKey']]);
        $pathNames = array_column($ancestors, $config['nameKey']);
        $pathNames[] = $node[$config['nameKey']];
        
        return implode($separator, $pathNames);
    }

    /**
     * 检查节点是否在指定路径下
     * @param int $nodeId
     * @param int $pathRootId
     * @return bool
     */
    public function isInPath(int $nodeId, int $pathRootId): bool
    {
        if ($nodeId == $pathRootId) {
            return true;
        }
        
        $ancestorIds = $this->getAllAncestorIds($nodeId);
        return in_array($pathRootId, $ancestorIds);
    }

    /**
     * 获取指定节点的所有同级节点
     * @param int $nodeId
     * @param array $fields 要获取的字段
     * @return array
     */
    public function getSameLevelNodes(int $nodeId, array $fields = ['*']): array
    {
        $config = $this->getTreeConfig();
        $node = $this->find($nodeId);
        if (!$node) {
            return [];
        }
        
        $level = $node[$config['levelKey']] ?? 1;
        return $this->getNodesByLevel($level, $fields);
    }

    /**
     * 获取指定节点的下一个兄弟节点
     * @param int $nodeId
     * @return array|null
     */
    public function getNextSibling(int $nodeId): ?array
    {
        $config = $this->getTreeConfig();
        $node = $this->find($nodeId);
        if (!$node) {
            return null;
        }
        
        $parentId = $node[$config['parentKey']];
        $sortValue = $node[$config['sortKey'] ?? $config['primaryKey']];
        
        return $this->where($config['parentKey'], $parentId)
            ->where($config['sortKey'] ?? $config['primaryKey'], '>', $sortValue)
            ->order($config['sortKey'] ?? $config['primaryKey'], 'asc')
            ->find();
    }

    /**
     * 获取指定节点的上一个兄弟节点
     * @param int $nodeId
     * @return array|null
     */
    public function getPrevSibling(int $nodeId): ?array
    {
        $config = $this->getTreeConfig();
        $node = $this->find($nodeId);
        if (!$node) {
            return null;
        }
        
        $parentId = $node[$config['parentKey']];
        $sortValue = $node[$config['sortKey'] ?? $config['primaryKey']];
        
        return $this->where($config['parentKey'], $parentId)
            ->where($config['sortKey'] ?? $config['primaryKey'], '<', $sortValue)
            ->order($config['sortKey'] ?? $config['primaryKey'], 'desc')
            ->find();
    }

    /**
     * 根据父ID获取树形结构
     * @param int $parentId
     * @param array $fields
     * @param array $conditions
     * @return array
     */
    public function getTreeByParentId(int $parentId = 0, array $fields = ['*'], array $conditions = []): array
    {
        $config = $this->getTreeConfig();
        $query = $this->where($config['parentKey'], $parentId);
        
        // 添加额外条件
        if (!empty($conditions)) {
            $query->where($conditions);
        }
        
        // 添加排序
        if (isset($config['sortKey'])) {
            $query->order($config['sortKey'], 'asc');
        }
        
        $nodes = $query->field($fields)->select()->toArray();
        
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
     * @param array $conditions
     * @return array
     */
    public function getAllTree(array $fields = ['*'], array $conditions = []): array
    {
        $config = $this->getTreeConfig();
        $query = $this;
        
        // 添加额外条件
        if (!empty($conditions)) {
            $query = $query->where($conditions);
        }
        
        // 添加排序
        if (isset($config['sortKey'])) {
            $query->order($config['sortKey'], 'asc');
        }
        
        $nodes = $query->field($fields)->select()->toArray();
        
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
        $config = $this->getTreeConfig();
        $node1 = $this->find($nodeId1);
        $node2 = $this->find($nodeId2);
        
        if (!$node1 || !$node2) {
            return false;
        }
        
        return $node1[$config['parentKey']] == $node2[$config['parentKey']];
    }

    /**
     * 获取节点路径文本（用于显示）
     * @param int $nodeId
     * @return string
     */
    public function getPathText(int $nodeId): string
    {
        $config = $this->getTreeConfig();
        $node = $this->find($nodeId);
        if (!$node || empty($node[$config['pathKey']])) {
            return $node[$config['nameKey']] ?? '';
        }
        
        $pathIds = explode($config['pathSeparator'], $node[$config['pathKey']]);
        $pathNames = [];
        
        foreach ($pathIds as $id) {
            if ($id > 0) {
                $pathNode = $this->find($id);
                if ($pathNode) {
                    $pathNames[] = $pathNode[$config['nameKey']];
                }
            }
        }
        
        return implode(' / ', $pathNames);
    }

    /**
     * 检查是否有子节点
     * @param int $nodeId
     * @return bool
     */
    public function hasChildren(int $nodeId): bool
    {
        $config = $this->getTreeConfig();
        return $this->where($config['parentKey'], $nodeId)->count() > 0;
    }

    /**
     * 获取直接子节点数量
     * @param int $nodeId
     * @return int
     */
    public function getChildrenCount(int $nodeId): int
    {
        $config = $this->getTreeConfig();
        return $this->where($config['parentKey'], $nodeId)->count();
    }

    /**
     * 获取所有子节点（包括自己）
     * @param int $nodeId
     * @return array
     */
    public function getAllChildren(int $nodeId): array
    {
        $config = $this->getTreeConfig();
        $ids = $this->getAllDescendantIds($nodeId);
        return $this->whereIn($config['primaryKey'], $ids)->select()->toArray();
    }

    /**
     * 移动节点到新的父节点
     * @param int $nodeId
     * @param int $newParentId
     * @return bool
     */
    public function moveNode(int $nodeId, int $newParentId): bool
    {
        $config = $this->getTreeConfig();
        
        // 检查是否移动到自己的后代
        if ($this->isDescendant($newParentId, $nodeId)) {
            return false;
        }
        
        // 更新父节点
        $this->where($config['primaryKey'], $nodeId)->update([
            $config['parentKey'] => $newParentId
        ]);
        
        // 更新路径
        return $this->updateNodePath($nodeId);
    }

    /**
     * 递归删除节点（软删除）
     * @param int $nodeId
     * @return bool
     */
    public function deleteRecursive(int $nodeId): bool
    {
        $config = $this->getTreeConfig();
        
        // 获取所有后代ID（包括自己）
        $allIds = $this->getAllDescendantIds($nodeId);
        
        if (empty($allIds)) {
            return false;
        }
        
        $this->startTrans();
        try {
            // 执行软删除
            $result = $this->destroy($allIds);
            
            if ($result === false) {
                throw new \Exception("删除节点失败");
            }
            
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            return false;
        }
    }

    /**
     * 恢复被删除的节点
     * @param int $nodeId
     * @return bool
     */
    public function restoreNode(int $nodeId): bool
    {
        $config = $this->getTreeConfig();
        
        // 获取所有后代ID（包括自己）
        $allIds = $this->getAllDescendantIds($nodeId);
        
        $this->startTrans();
        try {
            // 恢复删除
            $result = $this->onlyTrashed()
                ->whereIn($config['primaryKey'], $allIds)
                ->update([$config['deletedAtKey'] => null]);
            
            if ($result === false) {
                throw new \Exception("恢复节点失败");
            }
            
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            return false;
        }
    }
}