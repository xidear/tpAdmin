# TreeTrait 使用示例

## 概述

`TreeTrait` 是一个通用的树状结构处理 trait，提供了完整的树状数据操作方法，可以大大减少重复代码，提高代码复用性。

## 特性

- 🚀 **通用性强**: 支持任意字段名配置
- 🔧 **高度可配置**: 可自定义主键、父键、路径等字段名
- 📊 **完整功能**: 包含树构建、路径更新、节点操作等
- 🎯 **性能优化**: 支持条件查询和排序
- 🛡️ **安全可靠**: 包含事务处理和异常处理
- 🆕 **新增功能**: 兄弟节点、叶子节点、层级信息、批量操作等

## 基础配置

### 字段配置说明

```php
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
```

## 使用示例

### 1. Department 模型使用示例

```php
<?php
namespace app\model;

use app\common\BaseModel;
use app\common\trait\TreeTrait;
use think\model\concern\SoftDelete;

class Department extends BaseModel
{
    use SoftDelete, TreeTrait;

    protected function initialize()
    {
        parent::initialize();
        
        // 设置树形结构配置
        $this->setTreeConfig([
            'parentKey' => 'parent_id',
            'primaryKey' => 'department_id',
            'pathKey' => 'path',
            'levelKey' => 'level',
            'nameKey' => 'name',
            'childrenKey' => 'children',
            'pathSeparator' => ',',
            'sortKey' => 'sort',
            'statusKey' => 'status',
            'deletedAtKey' => 'deleted_at',
        ]);
    }

    // 使用 TreeTrait 提供的方法
    public function getDepartmentTree($conditions = [])
    {
        return $this->getAllTree(['*'], $conditions);
    }

    public function getChildrenDepartments($parentId)
    {
        return $this->getTreeByParentId($parentId);
    }
}
```

### 2. Menu 模型使用示例

```php
<?php
namespace app\model;

use app\common\BaseModel;
use app\common\trait\TreeTrait;

class Menu extends BaseModel
{
    use TreeTrait;

    protected function initialize()
    {
        parent::initialize();
        
        $this->setTreeConfig([
            'parentKey' => 'parent_id',
            'primaryKey' => 'menu_id',
            'pathKey' => 'path',
            'levelKey' => 'level',
            'nameKey' => 'name',
            'childrenKey' => 'children',
            'pathSeparator' => ',',
            'sortKey' => 'order_num',
            'statusKey' => 'visible',
            'deletedAtKey' => 'deleted_at',
        ]);
    }

    // 保留特殊的菜单路径构建逻辑
    public static function buildMenuTree(array $items, int $parentId = 0)
    {
        // 特殊的菜单路径构建逻辑
        // ... 保留原有实现
    }
}
```

### 3. Region 模型使用示例

```php
<?php
namespace app\model;

use app\common\BaseModel;
use app\common\trait\TreeTrait;
use think\model\concern\SoftDelete;

class Region extends BaseModel
{
    use SoftDelete, TreeTrait;

    protected function initialize()
    {
        parent::initialize();
        
        $this->setTreeConfig([
            'parentKey' => 'parent_id',
            'primaryKey' => 'region_id',
            'pathKey' => 'path',
            'levelKey' => 'level',
            'nameKey' => 'name',
            'childrenKey' => 'children',
            'pathSeparator' => '/',
            'sortKey' => 'snum',
            'statusKey' => 'status',
            'deletedAtKey' => 'deleted_at',
        ]);
    }

    // 保留高性能的树获取方法
    public static function getRegionTreeByParentId(int $parentId = 0, bool $recursive = false): array
    {
        // 高性能实现，保留不合并
        // ... 原有实现
    }

    public static function getAllRegionTree($level = 3): array
    {
        // 高性能实现，保留不合并
        // ... 原有实现
    }
}
```

## 主要方法说明

### 基础树操作

```php
// 获取完整树结构
$tree = $model->getAllTree();

// 根据父ID获取子树
$subTree = $model->getTreeByParentId($parentId);

// 获取所有后代ID
$descendantIds = $model->getAllDescendantIds($nodeId);

// 获取所有祖先ID
$ancestorIds = $model->getAllAncestorIds($nodeId);
```

### 新增的祖先节点操作

```php
// 获取所有祖先ID（不包括0）
$nonZeroAncestorIds = $model->getAllNonZeroAncestorIds($nodeId);

// 获取所有祖先信息（包括名称等）
$ancestors = $model->getAllAncestors($nodeId, ['id', 'name', 'level']);

// 获取节点的完整路径（包括名称）
$fullPath = $model->getFullPath($nodeId, ' > ');

// 检查节点是否在指定路径下
$isInPath = $model->isInPath($nodeId, $pathRootId);
```

### 兄弟节点操作

```php
// 获取所有兄弟节点ID（包括自己）
$allSiblingIds = $model->getAllSiblingIds($nodeId);

// 获取所有兄弟节点（包括自己）
$allSiblings = $model->getAllSiblings($nodeId, ['id', 'name']);

// 获取兄弟节点ID（不包括自己）
$siblingIds = $model->getSiblingIds($nodeId);

// 获取下一个兄弟节点
$nextSibling = $model->getNextSibling($nodeId);

// 获取上一个兄弟节点
$prevSibling = $model->getPrevSibling($nodeId);
```

### 叶子节点操作

```php
// 获取所有叶子节点ID
$leafNodeIds = $model->getLeafNodeIds($nodeId);

// 获取所有叶子节点
$leafNodes = $model->getLeafNodes($nodeId, ['id', 'name']);
```

### 层级操作

```php
// 获取指定层级的节点
$levelNodes = $model->getNodesByLevel(2, ['id', 'name'], ['status' => 1]);

// 获取指定节点的层级信息
$levelInfo = $model->getNodeLevelInfo($nodeId);
// 返回: ['node_id', 'level', 'ancestors', 'ancestor_count', 'is_root', 'is_leaf']

// 获取指定节点的所有同级节点
$sameLevelNodes = $model->getSameLevelNodes($nodeId);
```

### 统计信息

```php
// 获取树的统计信息
$stats = $model->getTreeStats($rootId);
// 返回: ['total_nodes', 'total_levels', 'leaf_nodes', 'internal_nodes', 'max_children', 'avg_children']
```

### 批量操作

```php
// 批量更新节点路径
$model->batchUpdatePaths([1, 2, 3, 4]);

// 批量移动节点
$model->batchMoveNodes([1, 2, 3], $newParentId);
```

### 路径操作

```php
// 更新节点路径
$model->updateNodePath($nodeId);

// 获取路径文本
$pathText = $model->getPathText($nodeId);
```

### 节点关系判断

```php
// 检查是否为祖先节点
$isAncestor = $model->isAncestor($ancestorId, $descendantId);

// 检查是否为后代节点
$isDescendant = $model->isDescendant($descendantId, $ancestorId);

// 检查是否为兄弟节点
$isSibling = $model->isSibling($nodeId1, $nodeId2);
```

### 节点操作

```php
// 移动节点
$model->moveNode($nodeId, $newParentId);

// 递归删除
$model->deleteRecursive($nodeId);

// 恢复删除
$model->restoreNode($nodeId);
```

## 高级用法

### 条件查询

```php
// 带条件的树查询
$tree = $model->getAllTree(['*'], ['status' => 1]);

// 带条件的子树查询
$subTree = $model->getTreeByParentId($parentId, ['*'], ['visible' => 1]);

// 带条件的层级查询
$levelNodes = $model->getNodesByLevel(2, ['*'], ['status' => 1]);
```

### 自定义配置

```php
// 动态修改配置
$model->setTreeConfig([
    'sortKey' => 'custom_sort',
    'pathSeparator' => '|'
]);

// 获取当前配置
$config = $model->getTreeConfig();
```

### 实际应用场景

```php
// 1. 面包屑导航
$breadcrumbs = $model->getAllAncestors($currentId, ['id', 'name']);
$breadcrumbPath = $model->getFullPath($currentId, ' > ');

// 2. 同级菜单切换
$nextMenu = $model->getNextSibling($currentMenuId);
$prevMenu = $model->getPrevSibling($currentMenuId);

// 3. 部门层级管理
$levelInfo = $model->getNodeLevelInfo($deptId);
if ($levelInfo['is_leaf']) {
    // 叶子部门，可以删除
}

// 4. 批量操作
$childDepts = $model->getAllDescendantIds($parentDeptId);
$model->batchUpdatePaths($childDepts);

// 5. 统计报表
$stats = $model->getTreeStats();
echo "总节点数: {$stats['total_nodes']}, 叶子节点: {$stats['leaf_nodes']}";
```

## 注意事项

1. **初始化配置**: 必须在 `initialize()` 方法中设置树形结构配置
2. **字段一致性**: 确保配置的字段名与数据库表结构一致
3. **性能考虑**: 对于大数据量的树操作，建议使用 Region 模型的高性能方法
4. **事务处理**: 涉及多表操作的删除、移动等操作已包含事务处理
5. **软删除**: 支持软删除，删除操作会标记 `deleted_at` 字段
6. **批量操作**: 批量操作包含事务处理，失败时会自动回滚

## 优势总结

- ✅ **代码复用**: 减少重复的树状结构代码
- ✅ **统一接口**: 提供一致的树操作方法
- ✅ **易于维护**: 集中管理树状结构逻辑
- ✅ **高度可配置**: 支持不同表结构的灵活配置
- ✅ **功能完整**: 覆盖常见的树状结构操作需求
- ✅ **性能优化**: 支持条件查询和批量操作
- ✅ **新增功能**: 丰富的兄弟节点、叶子节点、层级信息等操作
- ✅ **批量支持**: 支持批量更新、批量移动等操作
- ✅ **统计功能**: 提供完整的树状结构统计信息
