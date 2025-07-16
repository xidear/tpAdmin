<?php
//
//namespace app\common;
//
//use app\common\trait\BaseTrait;
//use app\common\trait\LaravelTrait;
//use think\Collection;
//use think\db\exception\DataNotFoundException;
//use think\db\exception\DbException;
//use think\db\exception\ModelNotFoundException;
//use think\db\Query;
//use think\db\Where;
//use think\Model;
//
//class BaseModel extends Model
//{
//    use LaravelTrait;
//    use BaseTrait;
//
//    public array $queryAppend=[];
//
//    /**
//     * 分页获取
//     * @return Collection|array
//     */
//    public function getPaginatedList(补充传参,$isSimple=false): Collection|array
//    {
//
//        //这里从request里面获取 page 和 list_rows参数 ,并
//
//        [$page,$listRows,$by,$order]=$this->parsePageQuery();
//
//        //下面用通用获取方法
//
//
//    }
//
//
//
//    /**
//     * 简单分页获取
//     * @return Collection|array
//     */
//    public function getSimplePaginatedList(补充传参): Collection|array
//    {
//
////            这里获取简单分页,复用上面的paginate方法
//
//
//
//    }
//
//
//
//    /**
//     * 不分页获取
//     * @return Collection|array
//     */
//    public function getAllList(补充传参,$isForce=false): Collection|array
//    {
//
////            这里获取不分页数据,复用通用获取方法
////        先计算总条数,大于某个数值(比如1000),截断,只获取1000条,如果$isForce==true,不考虑截断,不怕超时
//
//
//    }
//
//
//    /**
//     * 通用获取数据方法,不获取数据,只返回model或query
//     * @param $where
//     * @param $field
//     * @param $append
//     * @param $hidden
//     * @param $with
//     * @param $withWhere
//     * @param $isSimple
//     * @return BaseModel|array
//     */
//    public function getList(array|Query|Where|或者别的RAW|闭包等等可用的类型 $where=[],补充类型限制  $join=null,array|string|或者RAW等可用类型 $field="*",?array $append=[],?array $hidden=[],array|或者别的with可用的类型 $with=[],array|Query|Where|或者别的RAW|闭包等等可用的类型 $withWhere=[],?bool $isSimple=false,?string $order='desc',?string $by=null): BaseModel|array
//    {
//
////        join的结构我考虑如下 [表名=>'表名',join方式=>'默认inner',条件='',as=>'别名,默认原始表名',$filed='join中的字段,默认* ,这里要考虑是否有字段名重复的问题'] 是否合适
//        if ($by===null){
//            $by=self::getPk();
//        }else{
////          验证by有没有在所有表字段中(包括join里面的一个或多个表,如果指定字段要考虑字段)
//        }
////        验证order是不是 asc desc
//        try {
////            这里要考虑 传参
//            return self::append($append)->buildQuery();
//        } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
//            $this->reportError($e->getMessage(),(array)$e,$e->getCode());
//            return [];
//        }
//    }
//
//
//
//
//    /**
//     * 获取page和每页条数
//     * @return array
//     */
//    private function parsePageQuery(): array
//    {
//        $page=request()->param('page',1);
//        $listRows=request()->param('list_rows',15);
//        $by=request()->param('by',self::getPk());
//        $order=request()->param('order',"desc");
//        return [$page,$listRows,$by,$order];
//    }
//
//}


namespace app\common;

use think\{
    Collection,
    Model,
    Paginator,
    db\Query,
    db\Where
};
use think\db\exception\{DbException, DataNotFoundException, ModelNotFoundException};
use Closure;
use think\Paginator as ThinkPaginator;

class BaseModel extends Model
{
    // 默认配置
    protected $defaultPageSize = 15;
    protected $maxResults = 1000;

    /**
     * 获取分页数据
     */
    public function fetchPaginated(
        $conditions = [],
        array $config = []
    ): ThinkPaginator
    {
        // 处理配置参数
        $config = $this->prepareConfig($config);

        // 获取分页参数
        $page = $this->getPageParam($config);
        $pageSize = $this->getPageSizeParam($config);

        // 构建查询
        $query = $this->buildBaseQuery($conditions, $config);

        return $query->paginate(
            $pageSize,
            false,
            ['page' => $page]
        );
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
            'page' => 1,
            'pageSize' => $this->defaultPageSize,
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
    protected function buildBaseQuery($conditions, array $config): Query
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
        // 空条件直接返回
        if (!$conditions) return;

        if ($conditions instanceof Query || $conditions instanceof Where) {
            $query->where($conditions);
        } elseif ($conditions instanceof Closure) {
            $conditions($query);
        } elseif (is_array($conditions) || is_string($conditions)) {
            $query->where($conditions);
        }
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
        $page = $config['page'] ?? request()->param('page', 1);
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
}