###### PHEXT > [DataKit](../README.md) > [API Reference](index.md) > PackedSequence
------
### PackedSequence
A memory-efficient, variable-length array of fixed size elements.

A PackedSequence is sequentially indexed and non-associative.

All elements within the array must be the same amount of bytes. `NULL` values are not accepted.

Auto-packing and unpacking is available for values going in and out of the array.

It is particularly useful for large numerical arrays or indexes.

@implements \Iterator<int, int|float> @implements \ArrayAccess<int, int|float>
#### Methods
- [offsetSet](#offsetset)
- [offsetGet](#offsetget)
- [offsetExists](#offsetexists)
- [offsetUnset](#offsetunset)
- [__tostring](#__tostring)
- [rewind](#rewind)
- [current](#current)
- [key](#key)
- [next](#next)
- [valid](#valid)
- [__clone](#__clone)
- [__construct](#__construct)
- [count](#count)
- [print](#print)
- [add](#add)
- [insert](#insert)
- [set](#set)
- [get](#get)
- [delete](#delete)
- [pop](#pop)
- [shift](#shift)
- [clear](#clear)
- [keys](#keys)
- [empty](#empty)
- [first](#first)
- [last](#last)
- [any](#any)
- [all](#all)
- [contains](#contains)
- [ends_with](#ends_with)
- [starts_with](#starts_with)
- [filter](#filter)
- [map](#map)
- [pad](#pad)
- [head](#head)
- [tail](#tail)
- [slice](#slice)
- [sample](#sample)
- [rolling](#rolling)
- [clip](#clip)
- [swap](#swap)
- [sort](#sort)
- [reverse](#reverse)
- [sum](#sum)
- [avg](#avg)
- [max](#max)
- [min](#min)
- [normalise](#normalise)
- [normalize](#normalize)
- [product](#product)
- [variance](#variance)
- [round](#round)

------
##### offsetSet
```php
public function offsetSet(mixed $index, mixed $value) : void
```
No documentation available.


------
##### offsetGet
```php
public function offsetGet(mixed $index) : mixed
```
No documentation available.


------
##### offsetExists
```php
public function offsetExists(mixed $index) : bool
```
No documentation available.


------
##### offsetUnset
```php
public function offsetUnset(mixed $index) : void
```
No documentation available.


------
##### __tostring
```php
public function __tostring() : string
```
No documentation available.


------
##### rewind
```php
public function rewind() : void
```
No documentation available.


------
##### current
```php
public function current() : mixed
```
No documentation available.


------
##### key
```php
public function key() : mixed
```
No documentation available.


------
##### next
```php
public function next() : void
```
No documentation available.


------
##### valid
```php
public function valid() : bool
```
No documentation available.


------
##### __clone
```php
public function __clone() 
```
No documentation available.


------
##### __construct
```php
public function __construct(string|int $itemSize, array $startingValues = null) 
```
$itemSize should be either a string code accepted by PHP's built-in pack() method, or an integer specifying the raw byte size if no packing is required.

- **?list<int|float>** $startingValues is an optional array of starting numbers to add
to the array.


------
##### count
```php
public function count() : int
```
Return the amount of items within the Packed Sequence.


------
##### print
```php
public function print(string $prependMessage = '') : void
```
Print all values to the output buffer.


------
##### add
```php
public function add(string|int|float ...$values) : self
```
Add a value to the end of the array. If the value is an array or a traversable object then each element of it will instead be added.


------
##### insert
```php
public function insert(int $index, string|int|float $value) : self
```
Insert a new item into the array at a given index anywhere up to the end of the array.


------
##### set
```php
public function set(int $index, string|int|float $value) : self
```
Overwrite an existing value with the one provided. If $index is greater than the current count then the value is appended to the end.


------
##### get
```php
public function get(int $index) : string|int|float
```
Return an item from the array at the given index.


------
##### delete
```php
public function delete(int $index) : self
```
Remove an item from the array  at the given index.


------
##### pop
```php
public function pop(string|int|float|null &$poppedValue = null) : self
```
Pop an item off the end of the array. If $poppedValue is provided then it is filled with the value that was removed.


------
##### shift
```php
public function shift(string|int|float|null &$shiftedItem = null) : self
```
Shift an item off the start of the array. If $shiftedItem is provided then it is filled with the value that was removed.


------
##### clear
```php
public function clear() : self
```
Remove all elements from the array.


------
##### keys
```php
public function keys() : sqonk\phext\datakit\Vector
```
Return a new vector containing all indexes.


------
##### empty
```php
public function empty() : bool
```
Returns `TRUE` if there are 0 elements in the array, `FALSE` otherwise.


------
##### first
```php
public function first() : int|float
```
Return the first value in the array.


------
##### last
```php
public function last() : int|float
```
Return the last value in the array.


------
##### any
```php
public function any(mixed $match, bool $strict = false) : bool
```
Returns `TRUE` if any of the values within the array are equal to the value provided, `FALSE` otherwise.

A callback may be provided as the match to perform more complex testing.

Callback format: `myFunc($value) -> bool`

For basic (non-callback) matches, setting $strict to `TRUE` will enforce type-safe comparisons.


------
##### all
```php
public function all(mixed $match, bool $strict = false) : bool
```
Returns `TRUE` if all of the values within the array are equal to the value provided, `FALSE` otherwise.

A callback may be provided as the match to perform more complex testing.

Callback format: `myFunc($value) -> bool`

For basic (non-callback) matches, setting $strict to `TRUE` will enforce type-safe comparisons.


------
##### contains
```php
public function contains(int|float $needle) : bool
```
Search the array for the given needle (subject). This function is an alias of any().


------
##### ends_with
```php
public function ends_with(int|float $needle) : bool
```
Determines if the array ends with the needle.


------
##### starts_with
```php
public function starts_with(int|float $needle) : bool
```
Determines if the array starts with the needle.


------
##### filter
```php
public function filter(callable $callback) : self
```
Filter the contents of the array using the provided callback.

Callback format: `myFunc($value, $index): bool`


------
##### map
```php
public function map(callable $callback) : sqonk\phext\datakit\PackedSequence
```
Apply a callback function to the array.

Callback format: `myFunc($value, $index): mixed`


------
##### pad
```php
public function pad(int $count, int|float $value) : self
```
Pad the array to the specified length with a value. If $count is positive then the array is padded on the right, if it's negative then on the left.


------
##### head
```php
public function head(int $count) : sqonk\phext\datakit\PackedSequence
```
Return a copy of the array only containing the number of rows from the start as specified by $count.


------
##### tail
```php
public function tail(int $count) : sqonk\phext\datakit\PackedSequence
```
Return a copy of the array only containing the number of rows from the end as specified by $count.


------
##### slice
```php
public function slice(int $start, int $length = null) : sqonk\phext\datakit\PackedSequence
```
Return a copy of the array only containing the the rows starting from $start through to the given length.


------
##### sample
```php
public function sample(int $minimum, int $maximum = null) : sqonk\phext\datakit\PackedSequence
```
Return a copy of the array containing a random subset of the elements. The minimum and maximum values can be supplied to focus the random sample to a more constrained subset.


------
##### rolling
```php
public function rolling(int $window, callable $callback, int $minObservations = 0) : sqonk\phext\datakit\PackedSequence
```
Continually apply a callback to a moving fixed window on the sequence.

- **int** $window The size of the subset of the vector that is passed to the callback on each iteration. Note that this is the by default the maximum size the window can be. See `$minObservations`.
- **callable** $callback The callback method that produces a result based on the provided subset of data.
- **int** $minObservations The minimum number of elements that is permitted to be passed to the callback. If set to 0 the minimum observations will match whatever the window size is set to, thus enforcing the window size. If the value passed in is greater than the window size a warning will be triggered.

Callback format: `myFunc(Vector $rollingSet, mixed $index) : mixed`

**Returns:**  PackedSequence A PackedSequence of the same item size as the receiver, containing the series of results produced by the callback method.


------
##### clip
```php
public function clip($lower, $upper = null) : sqonk\phext\datakit\PackedSequence
```
Provide a maximum or minimum (or both) constraint for the values in the array.

If a value exceeds that constraint then it will be set to the constraint.

If either the lower or upper constraint is not needed then passing in null will ignore it.


------
##### swap
```php
public function swap(int $index1, int $index2) : sqonk\phext\datakit\PackedSequence
```
Swap the positions of 2 values within the array.


------
##### sort
```php
public function sort(bool $dir = ASCENDING) : sqonk\phext\datakit\PackedSequence
```
Sort the array in either `ASCENDING` or `DESCENDING` direction.


------
##### reverse
```php
public function reverse() : sqonk\phext\datakit\PackedSequence
```
Reserve the order of the elements.


------
##### sum
```php
public function sum() : int|float
```
Compute a sum of the values within the array.


------
##### avg
```php
public function avg() : int|float
```
Compute the average of the values within the array.


------
##### max
```php
public function max() : string|int|float|null
```
Return the maximum value present within the array.


------
##### min
```php
public function min() : string|int|float|null
```
Return the minimum value present within the array.


------
##### normalise
```php
public function normalise() : sqonk\phext\datakit\PackedSequence
```
Normalise the array to a range between 0 and 1.


------
##### normalize
```php
public function normalize() : sqonk\phext\datakit\PackedSequence
```
Alias of self::normalise().


------
##### product
```php
public function product() : int|float
```
Compute the product of the values within the array.


------
##### variance
```php
public function variance() : int|float
```
Compute the variance of values within the array. If the array is empty `FALSE` will be returned.


------
##### round
```php
public function round(int $precision, int $mode = PHP_ROUND_HALF_UP) : self
```
Round all values in the array up or down to the given decimal point precision.


------
