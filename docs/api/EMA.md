###### PHEXT > [DataKit](../README.md) > [API Reference](index.md) > EMA
------
### EMA
A simple class for management of a Exponential Moving Average. It works by alternating between adding new values to the array and calculating the current average.
#### Methods
- [count](#count)
- [getIterator](#getiterator)
- [offsetSet](#offsetset)
- [offsetGet](#offsetget)
- [offsetExists](#offsetexists)
- [offsetUnset](#offsetunset)
- [__construct](#__construct)
- [add](#add)
- [result](#result)
- [all](#all)
- [__tostring](#__tostring)

------
##### count
```php
public function count() : int
```
Returns the total number of averages calculated so far.


------
##### getIterator
```php
public function getIterator() : ArrayIterator
```
No documentation available.


------
##### offsetSet
```php
public function offsetSet($index, $value) : void
```
No documentation available.


------
##### offsetGet
```php
public function offsetGet($index) : mixed
```
No documentation available.


------
##### offsetExists
```php
public function offsetExists($index) : bool
```
No documentation available.


------
##### offsetUnset
```php
public function offsetUnset($index) : void
```
No documentation available.


------
##### __construct
```php
public function __construct(int $maxItems, int $defaultPrecision = null) 
```
Construct a new EMA with the specified maximum number of values.

- **$maxItems** The maximum amount of values that the moving average is allowed to work off of. As new values are added onto the end, old values are moved off the front.
- **$defaultPrecision** If set, will automatically round all averages to the given decimal precision.


------
##### add
```php
public function add(...$values) : sqonk\phext\datakit\EMA
```
Add one or more new values to the EMA. The value must be numerical in nature.


------
##### result
```php
public function result(int $precision = null) : float
```
Return the calculated result of the EMA as it currently stands. You can optionally pass in a value to $precision to control the amount of decimal places that the result is rounded to. If $precision is `NULL` then it falls back to the default precision specified at the time of object creation.


------
##### all
```php
public function all(int $precision = null) : array
```
Return all acquired averages, optionally rounding them to the specified precision. If $precision is `NULL` then it falls back to the default precision specified at the time of object creation.


------
##### __tostring
```php
public function __tostring() : string
```
No documentation available.


------
