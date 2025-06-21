<?php
namespace app\common\trait;
trait LaravelTrait {

    /**
     * @param $data
     * @return mixed
     */
    public function firstOrCreate($data): mixed
    {
        $first=self::where($data)->findOrEmpty();
        if ($first->isEmpty()){
            $first=$this->create($data);
        }
        return $first;
    }
}