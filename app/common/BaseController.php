<?php
declare (strict_types=1);

namespace app\common;

use app\common\trait\BaseTrait;
use app\common\trait\ReturnTrait;

/**
 * 增强的基础控制器
 * 继承官方BaseController并添加统一返回功能
 */
abstract class BaseController extends \app\BaseController
{

    use BaseTrait;
    use  ReturnTrait;
}