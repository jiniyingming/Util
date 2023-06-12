<?php

namespace common\helpers;

use Closure;
use JetBrains\PhpStorm\NoReturn;
use RuntimeException;

final class CounterSet
{
    private array $set = []; // 静态属性，保存集合数据

    /**
     *  CounterSet
     */
    private static array $funcSet = [];

    private const SELECTOR_BY_MAP = 1;
    private const SELECTOR_BY_INCR = 2;
    private array $selector;
    private string $categoryName;

    private int|float $count = -1;
    private int|float $incrVal = 0;

    /**
     * @var CounterSet
     */
    private static $_instance;

    /**
     * @param $categoryName
     */
    private function __construct($categoryName)
    {
        $this->categoryName = $categoryName;
    }

    public static function func($categoryName)
    {
        if (isset(self::$funcSet[$categoryName])) {
            self::$funcSet[$categoryName]->categoryName = $categoryName;

        } else {
            self::$funcSet[$categoryName] = new self($categoryName);
        }

        self::$_instance = self::$funcSet[$categoryName];
        return self::$_instance;
    }

    /*
     * 增加元素计数值
     */
    public function add($name): void
    {
        self::$_instance->check(self::SELECTOR_BY_MAP);
        self::$_instance->set[self::$_instance->categoryName][$name] = true;

    }

    /**
     * @param int|float $stepNum
     * @return void
     * 自增值
     */
    public function incr(int|float $stepNum = 1): void
    {
        self::$_instance->check(self::SELECTOR_BY_INCR);
        self::$_instance->incrVal < 0 && self::$_instance->incrVal = 0;
        self::$_instance->incrVal += $stepNum;
    }

    /**
     * 获取元素数量
     */
    public function getCount($default = null): ?int
    {
        self::$_instance->count = 0;
        switch (self::$_instance->selector[self::$_instance->categoryName] ?? self::SELECTOR_BY_MAP) {
            case self::SELECTOR_BY_INCR:
                self::$_instance->count = self::$_instance->incrVal;
                break;
            case self::SELECTOR_BY_MAP:
                if (!isset(self::$_instance->set[self::$_instance->categoryName])) {
                    break;
                }
                self::$_instance->count = count(self::$_instance->set[self::$_instance->categoryName]);
                break;
        }
        if ($default !== null && self::$_instance->count <= 0) {
            return $default;
        }
        return self::$_instance->count;
    }

    /**
     * @param bool $isTrue
     * @param int|float $val
     * @return void
     * 自增处理
     */
    public function planCollectWithIncr(bool $isTrue, int|float $val = 1): void
    {
        if ($isTrue) {
            self::$_instance->incr($val);
        }
    }

    /**
     * @param bool $isTrue
     * @param $val
     * @return void
     * 数组去重计数
     */
    public function planCollectWithAdd(bool $isTrue, $val): void
    {
        if ($isTrue) {
            self::$_instance->add($val);
        }
    }

    /**
     * @param int $type
     * @return void
     * 区分标记
     */
    private function check(int $type): void
    {
        if (isset(self::$_instance->selector[self::$_instance->categoryName]) && self::$_instance->selector[self::$_instance->categoryName] !== $type) {
            throw new RuntimeException('Category already exists');
        }
        if (!isset(self::$_instance->selector[self::$_instance->categoryName])) {
            self::$_instance->selector[self::$_instance->categoryName] = $type;
        }
    }

    /**
     * @param array $array
     * @param string $key
     * @param int|float|string|array|null $defaultVal
     * @param null $backType
     * @return mixed
     * 获取多维数组值
     */
    public static function toVal(array $array, string $key, int|float|string|array $defaultVal = null, $backType = null): mixed
    {
        $keys = explode('.', $key);
        foreach ($keys as $k) {
            if (isset($array[$k])) {
                $array = $array[$k];
            } else {
                return $defaultVal;
            }
        }
        return match ($backType) {
            'int' => (int)$array,
            'float' => (float)$array,
            'string' => (string)$array,
            'array' => array_filter((array)$array),
            default => $array,
        };
    }
    /**
     * @param $num
     * @param $sum
     * @return float
     * 计算两个数字之间的商，并且进行四舍五入、乘法和格式化输出的操作
     */
    public static function toRatio($num, $sum): float
    {
        return (float)bcmul(round(bcdiv($num, $sum, 10), 4, PHP_ROUND_HALF_DOWN), 100, 2);
    }

    /**
     * @return void
     * 清除周期内产生的缓存
     */
    #[NoReturn] public static function clearAll(): void
    {
        self::$funcSet = [];
        self::$_instance = null;
    }

    private function __clone()
    {
    }

}
