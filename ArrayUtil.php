<?php

namespace common\helpers;

use ArrayAccess;
use ArrayObject;
use Closure;
use Countable;
use ReturnTypeWillChange;
use function is_array;

class ArrayUtil implements ArrayAccess, Countable
{

    private ArrayObject $arrayObject;

    public function __construct(array $params = [])
    {
        $this->arrayObject = new ArrayObject($params);
    }


    /**
     * @param Closure $func
     * @return $this
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
     */
    public function unique(int $flags = SORT_STRING): ArrayUtil
    {
        return new self(array_unique($this->toArray(), $flags));
    }

    /**
     * @param $key
     * @param $indexKey
     * @return ArrayUtil
     */
    public function pluck($key, $indexKey = null): ArrayUtil
    {
        return new self(array_column($this->toArray(), $key, $indexKey));
    }

    public function reduce(Closure $callback, $initial = null)
    {
        return array_reduce($this->toArray(), $callback, $initial);
    }

    /**
     * @return array
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
     */
    public function sum($needle = null): int|float
    {
        return array_sum($this->pluck($needle)->toArray());
    }

    /**
     * @return int|float
     */
    #[ReturnTypeWillChange] public function count(): int|float
    {
        return $this->arrayObject->count();
    }

    /**
     * @param $needle
     * @return float
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
}
