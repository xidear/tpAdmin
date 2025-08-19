<?php

namespace app\common;

// 应用请求对象类
use app\common\exception\ValidationException;
use app\common\trait\BaseTrait;
use app\common\trait\ReturnTrait;
use app\common\ExtendValidate;
use app\model\Admin;
use app\Request;
use Exception;
use think\response\Json;
use think\Validate;

/**
 *   BaseRequest class
  * @method array message() 
  * @method array rules() 
  */
class BaseRequest extends Request
{
    public ?int $adminId = null;
    public ?Admin $admin = null;

    use ReturnTrait;
    use BaseTrait;

    /**
     *  批量验证
     * @var bool
     */
    protected bool $batch = false;


    /**
     * Request constructor.
     * @throws Exception
     */

    public function __construct()
    {
        parent::__construct();
        $this->validate();
    }


    /**
     * 初始化验证
     *
     * @time 2019年11月27日
     * @throws Exception
     */
    protected function validate(): static|Json
    {
        if (method_exists($this, 'rules')) {
            try {
                $validate = new  ExtendValidate();
                // 批量验证
                if ($this->batch) {
                    $validate->batch($this->batch);
                }
                // 验证
                $message = [];
                if (method_exists($this, 'message')) {
                    $message = $this->message();
                }
                if (!$validate->message(empty($message) ? [] : $message)->rule($this->rules())->check(request()->param())) {
                    throw new ValidationException($validate->getError());
                }
            } catch (Exception $e) {
                throw new ValidationException($e->getMessage());
            }
        }
        return $this;
    }


    /**
     * 过滤空字符串
     * @param array $array
     * @return array
     */
    public function filterSpace(array &$array): array
    {
        foreach ($array as $key => &$value) {
            if (is_string($value) || is_numeric($value)) {
                $value = trim($value);
                if ($value === '') {
                    unset($array[$key]);
                }
            } else if (is_array($value)) {
                $this->trim($value);
            }
        }
        return $array;
    }


    /**
     * 递归去除两端空格
     * @param array $params
     * @return array
     */

    public function trim(array &$params): array
    {
        foreach ($params as $key => &$value) {
            if (is_string($value) || is_numeric($value)) {
                $value = trim($value);
            } else if (is_array($value)) {
                $this->trim($value);
            }
        }
        return $params;
    }


    /**
     * 递归过滤空白参数
     * @param array $params The input array to filter.
     * @param bool $filterZero Whether to filter out zero values (default: false).
     * @param bool $filterNull Whether to filter out null values (default: true).
     * @param bool $filterEmptyArrays Whether to filter out empty arrays (default: true).
     * @param bool $filterEmptyStrings Whether to filter out empty strings (default: true).
     * @return array The filtered array.
     */

    public function filterArray(array $params, bool $filterZero = false, bool $filterNull = true, bool $filterEmptyArrays = true, bool $filterEmptyStrings = true): array
    {
        $result = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $filteredSubArray = $this->filterArray($value, $filterZero, $filterNull, $filterEmptyArrays, $filterEmptyStrings);
                // Include the filtered sub-array only if it's not empty
                if (!empty($filteredSubArray)) {
                    $result[$key] = $filteredSubArray;
                }
            } else {
                // Merge conditions and use continue when an element should be filtered
                if (
                    ($filterNull && is_null($value)) ||
                    ($filterZero && $value === 0) ||
                    ($filterEmptyArrays && is_array($value) && empty($value)) ||
                    ($filterEmptyStrings && is_string($value) && trim($value) === '')
                ) {
                    continue;
                }
                $result[$key] = $value;
            }
        }
        return $result;
    }
}

