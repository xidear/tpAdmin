<?php

namespace app\common;

use app\common\trait\BaseTrait;
use app\common\trait\LaravelTrait;
use think\Model;
use think\model\Pivot;

class BasePivot extends Pivot
{
    use BaseTrait;
    use LaravelTrait;
}