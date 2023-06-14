# Util Array 

## ArrayUtil 常用方法

### 实例化
```php
$collection = new ArrayUtil([
    [
        'user_id' => '1',
        'title' => 'Helpers in Laravel',
        'content' => 'Create custom helpers in Laravel',
        'category' => 'php'
    ],
    [
        'user_id' => '2',
        'title' => 'Testing in Laravel',
        'content' => 'Testing File Uploads in Laravel',
        'category' => 'php'
    ],
    [
        'user_id' => '3',
        'title' => 'Telegram Bot',
        'content' => 'Crypto Telegram Bot in Laravel',
        'category' => 'php'
    ],
]);
```

### where && orWhere
多维数组按指定条件筛选数据
```php
   $object = new ArrayUtil($data);
        $object->where(['content', 'like', 'testValid'])
            ->orWhere([
                'phone' => [1, 2, 3, 4],
                'a' => 1,
                ['d', '>=', 1]
            ])
            ->get();
```

### filter()
允许您使用回调过滤集合。 它只传递那些返回 true 的项。 所有其他项目都被删除。 filter 返回一个新实例而不更改原始实例。 它接受 value 和 key 作为回调中的两个参数。
```php
$filter = $collection->filter(function($value, $key) {
    if ($value['user_id'] == 2) {
        return true;
    }
});

$filter->all();
```

### search()
search 方法可以用给定的值查找集合。如果这个值在集合中，会返回对应的键。如果没有数据项匹配对应的值，会返回 false。

```php
$names = collect(['Alex', 'John', 'Jason', 'Martyn', 'Hanlin']);

$names->search('Jason');
```

## map()
map 方法用于遍历整个集合。 它接受回调作为参数。 value 和 key 被传递给回调。 回调可以修改值并返回它们。 最后，返回修改项的新集合实例。

```php
$changed = $collection->map(function ($value, $key) {
$value['user_id'] += 1;
return $value;
});

return $changed->all();
```
基本上，它将 user_id 增加 1。
上面代码的响应如下所示。


```php
[
    [
        "user_id" => 2,
        "title" => "Helpers in Laravel",
        "content" => "Create custom helpers in Laravel",
        "category" => "php"
    ],
];
```

## max()
max 方法返回给定键的最大值。 你可以通过调用 max 来找到最大的 user_id。 它通常用于价格或任何其他数字之类的比较，但为了演示，我们使用 user_id。 它也可以用于字符串，在这种情况下，Z> a。

```php
$collection->max('user_id');
```
上面的语句将返回最大的 user_id，在我们的例子中是 3。

## pluck()
pluck 方法返回指定键的所有值。 它对于提取一列的值很有用。

```php
$title = $collection->pluck('title');
$title->all();
```
结果看起来像这样。

```php
[
    "Helpers in Laravel",
    "Testing in Laravel",
    "Telegram Bot"
]
```
使用 eloquent 时，可以将列名作为参数传递以提取值。 pluck 也接受第二个参数，对于 eloquent 的集合，它可以是另一个列名。 它将导致由第二个参数的值作为键的集合。

```php
$title = $collection->pluck('user_id', 'title');
$title->all();
```
结果如下：


```php
[
    "Helpers in Laravel" => 1,
    "Testing in Laravel" => 2,
    "Telegram Bot" => 3
]
```


### avg()
avg 方法返回平均值。 你只需传递一个键作为参数，avg 方法返回平均值。 你也可以使用 average 方法，它基本上是 avg 的别名。

```php
$avg = new ArrayUtil([
['shoes' => 10],
['shoes' => 35],
['shoes' => 7],
['shoes' => 68],
])->avg('shoes');
```
上面的代码返回 30 ，这是所有四个数字的平均值。 如果你没有将任何键传递给 avg 方法并且所有项都是数字，它将返回所有数字的平均值。 如果键未作为参数传递且集合包含键 / 值对，则 avg 方法返回 0。

```php
$avg = new ArrayUtil([12, 32, 54, 92, 37]);
$avg->avg();
```
上面的代码返回 45.4，这是所有五个数字的平均值。

