<?php

namespace app\common;

use app\common\trait\BaseTrait;
use app\common\trait\LaravelTrait;
use think\Collection;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Model;

class BaseModel extends Model
{
    use LaravelTrait;
    use BaseTrait;

    public array $queryAppend=[];

    /**
     * 分页获取
     * @return Collection|array
     */
    public function selectPage(): Collection|array
    {

        //这里从request里面获取 page 和 list_rows参数 ,并

        [$page,$listRows]=$this->parsePageQuery();

        try {
            return self::append($this->queryAppend)->page($page, $listRows)->select();
        } catch (DataNotFoundException|ModelNotFoundException|DbException $e) {
            $this->reportError($e->getMessage(),(array)$e,$e->getCode());
            return [];
        }


    }

    /**
     * 获取page和每页条数
     * @return array
     */
    private function parsePageQuery(): array
    {
        $page=request()->param('page',1);
        $listRows=request()->param('list_rows',15);
        return [$page,$listRows];
    }

}