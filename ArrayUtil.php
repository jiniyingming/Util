<?php

namespace common\helpers;

use ArrayAccess;
use ArrayObject;
use Closure;
use Countable;
use ReturnTypeWillChange;
use RuntimeException;
use function in_array;
use function is_array;
use function is_int;
use function is_string;

class ArrayUtil implements ArrayAccess, Countable
{

    private ArrayObject $arrayObject;
    private array $condition;

    public function __construct(array $params = [])
    {
        $this->arrayObject = new ArrayObject($params);
        $this->params = [];
        $this->condition = [];
    }


    /**
     * @param Closure $func
     * @return $this
     * 迭代器循环
     * 匿名函数  val key
     */
    public function map(Closure $func): static
    {
        for ($iterator = $this->arrayObject->getIterator(); $iterator->valid(); $iterator->next()) {
            $item = $iterator->current();
            if (is_array($item)) {
                $item = new self($item);
            }
            $this->arrayObject->offsetSet($iterator->key(), $func($item, $iterator->key()));
        }
        return $this;
    }

    /**
     * @param Closure|null $func
     * @return ArrayUtil
     */
    public function filter(Closure $func = null): ArrayUtil
    {
        return new self(array_filter($this->toArray(), $func));
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->toArray());
    }


    /**
     * @param $needle
     * @param bool $strict
     * @return false|int|string
     */
    public function search($needle, bool $strict = false): false|int|string
    {
        return array_search($needle, $this->toArray(), $strict);
    }

    /**
     * @param int $flags
     * @return ArrayUtil
     * 去重
     */
    public function unique(int $flags = SORT_STRING): ArrayUtil
    {
        $stringifyArray = array_map('json_encode', $this->condition()->toArray());
        $uniqueArray = array_unique($stringifyArray, $flags);
        $uniqueMultidimensionalArray = array_map('json_decode', $uniqueArray);
        return new self($uniqueMultidimensionalArray);
    }

    /**
     * @param $key
     * @param $indexKey
     * @return ArrayUtil
     * 分割数组
     */
    public function pluck($key, $indexKey = null): ArrayUtil
    {
        return new self(array_column($this->condition()->toArray(), $key, $indexKey));
    }

    /**
     * @param Closure $callback
     * @param $initial
     * @return mixed|null
     */
    public function reduce(Closure $callback, $initial = null)
    {
        return array_reduce($this->condition()->toArray(), $callback, $initial);
    }

    /**
     * @return array
     * 转数组结构
     */
    public function toArray(): array
    {
        return $this->arrayObject->getArrayCopy();
    }

    /**
     * @param $needle
     * @return mixed
     */
    public function max($needle = null): mixed
    {
        return max($this->pluck($needle)->toArray());
    }

    /**
     * @param $needle
     * @return mixed
     */
    public function min($needle = null): mixed
    {
        return min($this->pluck($needle)->toArray());
    }

    /**
     * @param $needle
     * @return int|float
     * 取总
     */
    public function sum($needle = null): int|float
    {
        return array_sum($this->pluck($needle)->toArray());
    }

    /**
     * @return int|float
     * 数量值
     */
    #[ReturnTypeWillChange] public function count(): int|float
    {
        return $this->arrayObject->count();
    }

    /**
     * @return float|int
     * 去重后数量
     */
    public function uniqueCount(): float|int
    {
        return $this->unique()->count();
    }

    /**
     * @param $needle
     * @return float
     * 平均值
     */
    public function avg($needle = null): float
    {
        return $this->toRatio($this->count(), $this->sum($needle));
    }

    /**
     * @param $num
     * @param $sum
     * @return float
     */
    private function toRatio($num, $sum): float
    {
        return (float)bcmul(round(bcdiv($num, $sum, 10), 4, PHP_ROUND_HALF_DOWN), 100, 2);
    }


    /**
     * @param $offset
     * @return bool
     */
    #[ReturnTypeWillChange] public function offsetExists($offset): bool
    {
        return isset($this->toArray()[$offset]);
    }

    /**
     * @param $offset
     * @return mixed
     */
    #[ReturnTypeWillChange] public function offsetGet($offset): mixed
    {
        return $this->toArray()[$offset];
    }

    /**
     * @param $offset
     * @param $value
     * @return void
     */
    #[ReturnTypeWillChange] public function offsetSet($offset, $value): void
    {
        $this->toArray()[$offset] = $value;
    }

    /**
     * @param $offset
     * @return void
     */
    #[ReturnTypeWillChange] public function offsetUnset($offset): void
    {
        unset($this->toArray()[$offset]);
    }

    private const AND = 'AND';
    private const OR = 'OR';

    /**
     * @param array $params
     * @return $this
     * 并查询 支持区间范围查询
     * 支持闭包处理查询结果
     */
    public function where(array $params): static
    {
        $this->addParams($params, self::AND);
        return $this;
    }

    /**
     * @param array $params
     * @return $this
     * 或查询 支持区间范围查询
     * 支持闭包处理查询结果
     */
    public function orWhere(array $params): static
    {
        $this->addParams($params, self::OR);
        return $this;
    }

    private array $params;

    /**
     * @param $params
     * @param $condition
     * @return void
     */
    private function addParams($params, $condition): void
    {
        if (!empty($params)) {
            foreach (self::TERM_MAP as $sign) {
                $i = array_search($sign, $params, true);
                if ($i !== false && is_int($i)) {
                    $params[] = [$params[$i - 1], $params[$i], $params[$i + 1]];
                    unset($params[$i - 1], $params[$i], $params[$i + 1]);
                }
            }
            foreach ($params as $name => $value) {
                if (is_int($name)) {
                    $this->params[$condition][] = $value;
                } else {
//                    if (isset($this->params[$condition][$name])) {
                    $this->params[$condition][] = [$name => $value];
//                    } else {
//                        $this->params[$condition][$name] = $value;
//                    }
                }
            }
        }

    }


    /**
     * 映射范围查询类
     */
    private const TERM_MAP = [
        '>', '>=', '<', '<=', '<>', 'contains'
    ];

    /**
     * @return $this|ArrayUtil
     * 获取查询结果集
     * 大部分场景用于where条件结束语
     */
    public function get(): ArrayUtil|static
    {
        return $this->condition();
    }

    /**
     * @return $this
     */
    private function condition(): ArrayUtil|static
    {
        if ($this->params) {
            foreach ($this->params as $condition => $param) {
                $this->condition[$condition] = $this->dealQuery($param, $condition);
            }
            return $this->queryData();
        }

        return $this;
    }

    /**
     * @param $value
     * @return $this
     * 原始类追加元素
     */
    public function append($value): static
    {
        $this->arrayObject->append($value);
        return $this;
    }

    /**
     * @return ArrayUtil
     * 生成查询结果
     */
    private function queryData(): ArrayUtil
    {
        $map = [];
        foreach ($this->condition as $object) {
            $map = array_merge(array_keys($object->filter()->toArray()), $map);
        }
        $data = new self();
        foreach ($map as $offset) {
            if ($this->arrayObject->offsetExists($offset)) {
                $data->append($this->arrayObject->offsetGet($offset));
            }
        }
        return $data;
    }


    /**
     * @param array $param
     * @param string $condition
     * @return $this
     * 处理条件
     */
    private function dealQuery(array $param, string $condition): static
    {
        $dealClosureFunc = function ($val, $name, $func) {
            if (!isset($val[$name])) {
                throw new RuntimeException(sprintf('键名：%s 不存在', $name));
            }
            return $func($val[$name]);
        };

        $dealChooseFunc = function ($val, $data) {
            return match ($data[1]) {
                '>' => $val[$data[0]] > $data[2],
                '<' => $val[$data[0]] < $data[2],
                '>=' => $val[$data[0]] >= $data[2],
                '<=' => $val[$data[0]] <= $data[2],
                '<>' => strcmp($val[$data[0]], $data[2]) !== 0,
                'contains' => str_contains($val[$data[0]], $data[2]),
            };
        };

        $dealKeyValFunc = function ($val, $name, $data) {
            $isTrue = false;
            if (!isset($val[$name])) {
                throw new RuntimeException(sprintf('键名：%s 不存在', $name));
            }
            if (is_array($data)) {
                if (!is_array($val[$name])) {
                    $isTrue = in_array($val[$name], $data, false);
                }
            } else {
                $isTrue = strcmp($val[$name], $data) === 0;
            }
            return $isTrue;
        };
        return $this->filter()->map(function ($val) use ($condition, $param, $dealClosureFunc, $dealChooseFunc, $dealKeyValFunc) {
            $isTrue = false;
            $judgeMap = [];
            foreach ($param as $name => $data) {
                if ($data instanceof Closure) {//-- 键 => function
                    $isTrue = $dealClosureFunc($val, $name, $data);
                } elseif (is_string($name)) {//-- 键 => 值
                    $isTrue = $dealKeyValFunc($val, $name, $data);
                } else if (is_array($data)) {//-- 键 => 比较符 => 值
                    $size = count($data);
                    switch ($size) {
                        case 1://-- 键 => 值
                            $key = array_key_first($data);
                            if ($data[$key] instanceof Closure) {
                                $isTrue = $dealClosureFunc($val, $key, $data[$key]);
                            } else {
                                $isTrue = $dealKeyValFunc($val, $key, $data[$key]);
                            }
                            break;
                        case 3:
                            $isTrue = $dealChooseFunc($val, $data);
                            break;
                        case 2:

                            break;
                    }
                }
                $judgeMap[] = $isTrue;
            }

            if ($condition === self::AND) {
                return !in_array(false, $judgeMap);
            }
            if ($condition === self::OR) {
                return in_array(true, $judgeMap);
            }
            return $isTrue;

        });
    }

    /**
     * @param $key
     * @return ArrayUtil
     * 分组动作
     */
    public function groupBy($key): ArrayUtil
    {
        return new self(array_reduce($this->condition()->toArray(), static function ($result, $item) use ($key) {
            $result[$item[$key]][] = $item;
            return $result;
        }, []));
    }

    /**
     * @return ArrayUtil
     * 数组多维降维合并
     */
    public function convertMerge(): ArrayUtil
    {
        $newMap = [];
        $this->map(function ($val) use (&$newMap) {
            $newMap = array_merge($newMap, $val->toArray());
            return;
        });
        return new self($newMap);
    }


}