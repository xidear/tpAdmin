<?php


namespace app\common;

use app\common\trait\BaseTrait;
use app\common\trait\LaravelTrait;
use Closure;
use ReflectionClass;
use ReflectionMethod;
use think\{Collection,
    db\Query,
    db\Where,
    facade\Log,
    helper\Str,
    Model,
    model\contract\Modelable,
    model\relation\BelongsToMany,
    model\relation\HasMany};
use think\db\exception\{DataNotFoundException, DbException, ModelNotFoundException};
use Throwable;
use app\common\trait\RewriteCollectionTrait;

class BaseModel extends Model
{
    use BaseTrait;
    use LaravelTrait;
    use RewriteCollectionTrait;

    protected bool $autoWriteTimestamp = true;

    // 默认配置
    protected int $defaultPageSize = 15;
    protected int $maxResults = 1000;


    public function getByKey($id){

        $pk=$this->getPk();
        $string="getBy".Str::studly($pk);
        return self::$string($id);

    }

    public function columnToString(Collection|HasMany|BelongsToMany|Query $collection, $columnName, $sep = ",")
    {
        if ($collection instanceof Collection) {
            $array = array_column($collection->toArray(), $columnName);
            return implode($sep, $array);
        } else {
            return implode($sep,$collection->column( $columnName));
        }
    }

    public function fetchData(array $conditions=[],array $config=[]): \think\Collection|array
    {
        if (request()->has("page","get")||request()->has("list_rows","get")) {
            if (request()->has("page","get")){
                $config['pageNum']=request()->get("page",1);
            }
            if (request()->has("list_rows","get")){
                $config['pageSize']=request()->get("list_rows",15);
            }
            return $this->fetchPaginated($conditions,$config);
        }
        return $this->fetchAll($conditions,$config,true);



    }

    /**
     * 获取分页数据
     */
    public function fetchPaginated(
        $conditions = [],
        array $config = []
    ): array
    {
        // 处理配置参数
        $config = $this->prepareConfig($config);

        // 获取分页参数
        $page = $this->getPageParam($config);
        $pageSize = $this->getPageSizeParam($config);

        // 构建查询
        $query = $this->buildBaseQuery($conditions, $config);

        $total = $query->count();
        try {
            $list = $query->page(
                $page,
                $pageSize
            )->select()->toArray();
        } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
//            这里写入错误日志
//            Log::error(文件名,方法,前端传参,方法传参,报错信息,用户(如果已登录)$e->getMessage());
            $list = [];
        }
        return ['total' => $total, 'list' => $list,'current_page' => $page,'per_page' => $pageSize];
    }

    /**
     * 获取所有数据
     */
    public function fetchAll(
        $conditions = [],
        array $config = [],
        bool $force = false
    ): Collection
    {
        // 处理配置参数
        $config = $this->prepareConfig($config);

        // 构建查询
        $query = $this->buildBaseQuery($conditions, $config);

        // 强制获取时不做限制
        if ($force) {
            return $query->select();
        }

        // 执行安全数量检查
        $total = $query->count();
        if ($total > $this->maxResults) {
            return $query->limit($this->maxResults)->select();
        }

        return $query->select();
    }

    /**********************
     * 受保护的核心方法（避免与TP父类冲突）
     *********************/

    /**
     * 准备配置
     */
    protected function prepareConfig(array $config): array
    {

        return array_merge([
            'page' => null,
            'pageSize' => null,
            'fields' => null,
            'append' => [],
            'hidden' => [],
            'with' => [],
            'join' => [],
            'orderBy' => $this->getPk(),
            'orderDir' => 'desc'
        ], $config);
    }

    /**
     * 构建基础查询
     */
    protected function buildBaseQuery($conditions, array $config): BaseModel|Query
    {
        $query = $this->newQuery();

        // 添加查询条件
        $this->applyQueryConditions($query, $conditions);

        // 添加字段
        $this->applyQueryFields($query, $config['fields'] ?? null);

        // 添加关联
        $this->applyQueryRelations($query, $config['with'] ?? []);

        // 添加表连接
        $this->applyQueryJoins($query, $config['join'] ?? []);

        // 添加排序
        $this->applyQueryOrder($query, $config['orderBy'] ?? '', $config['orderDir'] ?? '');

        // 添加模型属性
        $this->applyModelAttributes($query, $config['append'] ?? [], $config['hidden'] ?? []);

        return $query;
    }

    /**
     * 应用查询条件
     */
    protected function applyQueryConditions(Query $query, $conditions): void
    {

        // 1. 空条件返回
        if (empty($conditions)) {
            return;
        }

        // 2. 主键查询优先
        if ($this->isPrimaryKeyCondition($conditions)) {
            $query->where($this->getPk(), $conditions);
            return;
        }

        // 3. 闭包条件
        if ($conditions instanceof Closure) {
            $conditions($query);
            return;
        }

        // 4. ThinkPHP查询对象
        if ($conditions instanceof Query || $conditions instanceof Where) {
            $query->where($conditions);
            return;
        }

        // 5. 数组条件
        if (is_array($conditions)) {
            $query->where($conditions);
            return;
        }

        // 6. 字符串条件
        if (is_string($conditions)) {
            $query->whereRaw($conditions);
        }


    }


    /**
     * 判断是否主键
     * @param $conditions
     * @return bool
     */
    protected function isPrimaryKeyCondition($conditions): bool
    {
        // 纯数字（整数主键）
        if (is_numeric($conditions)) {
            return true;
        }

        // 不含空格的字符串（字符串主键）
        if (is_string($conditions) && !preg_match('/\s+/', $conditions)) {
            return true;
        }

        return false;
    }


    /**
     * 应用查询字段
     */
    protected function applyQueryFields(Query $query, $fields = null): void
    {
        // 未指定字段时使用默认
        if (!$fields) return;

        $query->field($fields);
    }

    /**
     * 应用查询关联
     */
    protected function applyQueryRelations(Query $query, $relations = []): void
    {
        if (!$relations) return;

        $query->with($relations);
    }

    /**
     * 应用查询连接
     */
    protected function applyQueryJoins(Query $query, array $joins = []): void
    {
        if (!$joins) return;

        foreach ($joins as $join) {
            // 有效JOIN必须包含表和条件
            if (!($join['table'] ?? false) || !($join['on'] ?? false)) continue;

            $table = $join['table'];
            $on = $join['on'];
            $type = $join['type'] ?? 'INNER';
            $alias = $join['alias'] ?? '';

            $query->join($table, $on, $type, $alias);

            // 添加JOIN字段
            if ($join['fields'] ?? false) {
                $query->field($join['fields']);
            }
        }
    }

    /**
     * 应用查询排序
     */
    protected function applyQueryOrder(Query $query, $field = '', $direction = ''): void
    {
        // 排序字段默认主键
        $field = $field ?: $this->getPk();

        // 排序方向验证
        $direction = in_array(strtolower($direction), ['asc', 'desc'])
            ? strtoupper($direction)
            : 'DESC';

        $query->order($field, $direction);
    }

    /**
     * 应用模型属性
     */
    protected function applyModelAttributes(Query $query, array $append = [], array $hidden = []): void
    {
        if ($append) {
            $query->append($append);
        }

        if ($hidden) {
            $query->hidden($hidden);
        }
    }

    /**
     * 获取分页参数
     */
    protected function getPageParam(array $config): int
    {
        $page = $config['pageNum'] ?? request()->param('page', 1);
        return is_numeric($page) ? (int)$page : 1;
    }

    /**
     * 获取分页大小参数
     */
    protected function getPageSizeParam(array $config): int
    {
        $size = $config['pageSize'] ?? request()->param('list_rows', $this->defaultPageSize);
        return is_numeric($size) ? (int)$size : $this->defaultPageSize;
    }

    /**********************
     * 实用助手方法
     *********************/

    /**
     * 创建条件关联
     */
    public function createRelationCondition(string $relation, $condition): array
    {
        return [
            $relation => function ($query) use ($condition) {
                if ($condition instanceof Closure) {
                    $condition($query);
                } else {
                    $query->where($condition);
                }
            }
        ];
    }

    /**
     * 创建字段关联
     */
    public function createRelationFields(string $relation, array $fields): array
    {
        return [
            $relation => function ($query) use ($fields) {
                $query->field($fields);
            }
        ];
    }

    /**
     * 创建连接参数
     */
    public static function createJoinParams(
        string $table,
        string $on,
               $fields = [],
        string $type = 'INNER',
        string $alias = ''
    ): array
    {
        return [
            'table' => $table,
            'on' => $on,
            'fields' => $fields,
            'type' => $type,
            'alias' => $alias
        ];
    }

    /**
     * 获取请求分页参数
     */
    public function getRequestPageParams(): array
    {
        return [
            'page' => $this->getPageParam([]),
            'pageSize' => $this->getPageSizeParam([])
        ];
    }


    /**
     * 获取单条数据（优化版本）
     */
    public function fetchOne($conditions, array $config = []): Model
    {
        // 处理配置参数
        $config = $this->prepareConfig($config);

        // 构建查询
        $query = $this->buildBaseQuery($conditions, $config);

        // 获取结果（找不到时返回空模型）
        return $query->findOrEmpty();
    }

    /**
     * 查询数据或创建（使用fetchOne优化）
     */
    public function fetchOneOrCreate($data): Model|Modelable
    {
        // 使用fetchOne替代直接查询
        $model = $this->fetchOne($data);

        // 如果找到则返回
        if (!$model->isEmpty()) {
            return $model;
        }

        // 否则创建新记录
        return $this->create($data);
    }


    /**
     * 智能创建数据（主模型+一对多关联）
     * @param array $data 创建数据
     * @return BaseModel|false 创建成功返回模型实例，失败返回false
     */
    public function intelligentCreate(array $data): BaseModel|false
    {
        // 开启事务（与更新方法保持一致的事务处理）
        $this->startTrans();
        try {
            // 1. 复用数据分离逻辑：分离主模型数据和关联数据
            [$mainData, $relationsData] = $this->separateData($this, $data);

            // 2. 复用字段过滤逻辑：只保留主模型有效字段（保留空值）
            $mainData = $this->filterValidModelData($this, $mainData);

            // 3. 创建主模型记录（获取自动生成的主键）
            $this->save($mainData);
            $mainModel = $this; // 保存创建后的模型实例（含主键）

            // 4. 复用关联保存逻辑：处理一对多关联（创建时无需删除现有关联，$deleteExisting设为false）
            foreach ($relationsData as $relationName => $relationItems) {
                $this->saveHasManyRelation($mainModel, $relationName, $relationItems, false);
            }

            $this->commit();
            return $mainModel; // 返回创建后的主模型实例（含主键和关联关系）

        } catch (Throwable $e) {
            $this->rollback();
            Log::error('智能创建失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 智能更新数据（主模型+一对多关联）
     * @param array $data 更新数据
     * @return bool 更新结果
     */
    public function intelligentUpdate(array $data): bool
    {
        // 开启事务
        $this->startTrans();
        try {


            // 4. 分离主模型数据和关联数据
            [$mainData, $relationsData] = $this->separateData($this, $data);
            // 只有当主模型数据非空时才处理更新
            if (!empty($mainData)) {
                // 过滤有效字段（保留空值）
                $mainData = $this->filterValidModelData($this, $mainData);

                // 移除主键字段（防止意外更新主键）
                $pk = $this->getPk();
                unset($mainData[$pk]);

                // 只有存在非主键字段的数据时才执行更新
                if (!empty($mainData)) {
                    $this->save($mainData);
                }
            }


            // 6. 处理一对多关联（保留空值）
            foreach ($relationsData as $relationName => $relationItems) {
                $this->saveHasManyRelation($this, $relationName, $relationItems, true);
            }

            $this->commit();
            return true;

        } catch (Throwable $e) {
            $this->rollback();
            Log::error('智能更新失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 过滤有效模型数据（保留空值）
     */
    protected function filterValidModelData(Model $model, array $data): array
    {
        // 获取所有有效字段（主表字段和JSON字段）
        $validFields = $model->getTableFields();

        // 移除主键（防止意外更新）
        $pk = $model->getPk();
        unset($data[$pk]);

        // 只保留有效字段，包括值为空的
        return array_intersect_key($data, array_flip($validFields));
    }

    /**
     * 分离主模型数据和关联数据
     */
    protected function separateData(Model $model, array $data): array
    {
        $mainData = [];
        $relationsData = [];

        // 获取所有有效字段
        $modelFields = $model->getTableFields();

        foreach ($data as $key => $value) {
            if (in_array($key, $modelFields)) {
                // 主模型字段（保留空值）
                $mainData[$key] = $value;
            } elseif (is_array($value)) {
                // 关联数据（保留空值）
                $relationsData[$key] = $value;
            }
        }

        return [$mainData, $relationsData];
    }


    /**
     * 保存一对多关联（支持替换/增量更新，保留空值）
     * @param Model $model 主模型实例
     * @param string $relationName 关联方法名（如：'adminRoles'）
     * @param array $relationItems 关联数据列表
     * @param bool $deleteExisting 是否先删除已有的关联数据（true=完全替换，false=增量更新）
     */
    protected function saveHasManyRelation(Model $model, string $relationName, array $relationItems, bool $deleteExisting = false): void
    {
        // 1. 验证关系类型
        if (!method_exists($model, $relationName)) {
            Log::warning("尝试更新未定义的关联: $relationName");
            return;
        }

        $relation = $model->$relationName();
        if (!$relation instanceof HasMany) {
            Log::warning("尝试更新非HasMany关系: {$relationName}（仅支持一对多关联）");
            return;
        }

        // 2. 如需完全替换关联数据，先删除已有的关联
        if ($deleteExisting) {
            // 删除当前主模型下的所有关联数据（基于外键）
            try {
                $relation->delete();
            } catch (DbException $e) {
                Log::warning("删除关联发生异常" . $e->getMessage());
                return;
            }
        }

        // 3. 准备有效数据（保留空值，过滤无效字段）
        $relatedModel = $relation->getModel();
        $relatedFields = $relatedModel->getTableFields();
        $validItems = [];

        foreach ($relationItems as $item) {
            $validItem = [];
            foreach ($item as $key => $value) {
                // 只保留关联模型的有效字段（包括空值）
                if (in_array($key, $relatedFields)) {
                    $validItem[$key] = $value;
                }
            }
            $validItems[] = $validItem;
        }

        // 4. 批量保存关联数据（更新/新增）
        if (!empty($validItems)) {
            $model->$relationName()->saveAll($validItems);
        }
    }

    /**
     * 批量删除数据及其关联的HasMany关系数据（优化模型版）
     *
     * @param array $ids 要删除的主键ID数组
     * @return bool|string 成功返回true，失败返回错误信息
     */
    public function batchDeleteWithRelation(array $ids, array $relationList = []): bool|string
    {
        if (empty($ids)) {
            return $this->false('请提供要删除的数据ID');
        }

        // 验证所有ID是否存在
        $existingIds = $this->where($this->getPk(), 'in', $ids)
            ->column($this->getPk());

        $nonExistingIds = array_diff($ids, $existingIds);
        if (!empty($nonExistingIds)) {
            return $this->false('以下ID不存在: ' . implode(', ', $nonExistingIds));
        }

        // 开启事务
        $this->startTrans();
        try {
            // 1. 批量删除所有关联数据
            $relations = $this->getDeletableRelations($relationList);


            foreach ($relations as $config) {
                // 使用关联模型进行批量删除
                $relationModel = $config['relationModel'];

                $relationModel->where($config['foreign_key'], 'in', $ids)
                    ->delete();
            }

            // 2. 删除主表数据
            $this->where($this->getPk(), 'in', $ids)
                ->delete();

            $this->commit();
            return true;

        } catch (Throwable $e) {
            $this->rollback();
            Log::error('批量删除失败: ' . $e->getMessage());
            return $this->false('批量删除操作失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取模型的可删除关联关系配置（优化版）
     */
    protected function getDeletableRelations(array $relationList = []): array
    {
        static $cache = [];
        $className = static::class;

        // 使用缓存避免重复解析
        if (isset($cache[$className])) {
            return $cache[$className];
        }

        $relations = [];

        // 使用反射获取所有公共方法
        $reflection = new ReflectionClass($this);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $methodName = $method->getName();

            // 跳过魔术方法和基类方法
            if (str_starts_with($methodName, '__') || $method->class === Model::class) {
                continue;
            }
            if (!empty($relationList)) {
                if (!in_array($methodName, $relationList)) {
                    continue;
                }
            }

            try {
                // 获取关联实例
                $relation = $this->$methodName();

                // 只处理HasMany关系
                if ($relation instanceof HasMany) {
                    // 获取关联模型实例
                    $relationModel = $relation->getModel();
                    $foreignKey = $relation->getForeignKey();

                    // 构建关系配置
                    $relations[$methodName] = [
                        'relationModel' => $relationModel,
                        'foreign_key' => $foreignKey,
                    ];
                }
            } catch (Throwable $e) {
                // 忽略调用错误的关系
            }
        }

        $cache[$className] = $relations;
        return $relations;
    }

}