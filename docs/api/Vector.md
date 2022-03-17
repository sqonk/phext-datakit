###### PHEXT > [DataKit](../README.md) > [API Reference](index.md) > Vector
------
### Vector
A class to add both object orientation and utility methods to native arrays, enabling easier to write and easier to read code.

In particular it sports a variety of basic mathematical and statistical functions.
#### Methods
- [getIterator](#getiterator)
- [offsetSet](#offsetset)
- [offsetGet](#offsetget)
- [offsetExists](#offsetexists)
- [offsetUnset](#offsetunset)
- [__tostring](#__tostring)
- [__construct](#__construct)
- [array](#array)
- [count](#count)
- [constrain](#constrain)
- [append](#append)
- [add](#add)
- [merge](#merge)
- [set](#set)
- [prepend](#prepend)
- [fill](#fill)
- [prefill](#prefill)
- [get](#get)
- [remove](#remove)
- [remove_range](#remove_range)
- [clear](#clear)
- [isset](#isset)
- [keys](#keys)
- [empty](#empty)
- [values](#values)
- [unique](#unique)
- [frequency](#frequency)
- [prune](#prune)
- [first](#first)
- [last](#last)
- [middle](#middle)
- [choose](#choose)
- [occurs_in](#occurs_in)
- [any](#any)
- [all](#all)
- [filter](#filter)
- [intersect](#intersect)
- [diff](#diff)
- [only_keys](#only_keys)
- [contains](#contains)
- [ends_with](#ends_with)
- [starts_with](#starts_with)
- [trim](#trim)
- [implode](#implode)
- [implode_only](#implode_only)
- [map](#map)
- [chunk](#chunk)
- [pad](#pad)
- [pop](#pop)
- [shift](#shift)
- [transpose](#transpose)
- [groupby](#groupby)
- [splitby](#splitby)
- [sort](#sort)
- [ksort](#ksort)
- [keyed_sort](#keyed_sort)
- [shuffle](#shuffle)
- [rotate_back](#rotate_back)
- [rotate_right](#rotate_right)
- [rotate_forward](#rotate_forward)
- [rotate_left](#rotate_left)
- [head](#head)
- [tail](#tail)
- [slice](#slice)
- [sample](#sample)
- [clip](#clip)
- [reverse](#reverse)
- [flip](#flip)
- [sum](#sum)
- [avg](#avg)
- [max](#max)
- [min](#min)
- [median](#median)
- [product](#product)
- [cumsum](#cumsum)
- [cummax](#cummax)
- [cummin](#cummin)
- [cumproduct](#cumproduct)
- [variance](#variance)
- [reduce](#reduce)
- [normalise](#normalise)
- [normalize](#normalize)
- [round](#round)

------
##### getIterator
```php
public function getIterator() : Iterator
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
##### __tostring
```php
public function __tostring() : string
```
No documentation available.


------
##### __construct
```php
public function __construct(array $startingArray = []) 
```
Construct a new vector with the provided array.


------
##### array
```php
public function array() : array
```
No documentation available.


------
##### count
```php
public function count() : int
```
Return the number of elements in the array.


------
##### constrain
```php
public function constrain(int $limit) : sqonk\phext\datakit\Vector
```
Set a rolling capacity limit on the vector. Once set, old values will be shifted off of the beginning to make room for new values once the capacity is reached.

Setting the limit to 0 will remove the constraint altogether, which is the default.


------
##### append
```php
public function append($value) : sqonk\phext\datakit\Vector
```
Add one element to the end of the vector. Slightly faster than using add() in a tight loop.


------
##### add
```php
public function add(...$values) : sqonk\phext\datakit\Vector
```
Add one or more elements to the end of the vector.


------
##### merge
```php
public function merge(iterable $collection, bool $maintainKeyAssociation = false) : sqonk\phext\datakit\Vector
```
Append another array, vector or collection to the end of the vector.

@param $collection The set of items to add to the end of the vector. @param $maintainKeyAssociation When ``TRUE``, both the keys and the values from the given collection will be merged into the vector. When ``FALSE``, only the values will. It should be noted that this method will not attempt to modify the keys/indexes already in the vector prior to the merge.


------
##### set
```php
public function set($key, $value) : sqonk\phext\datakit\Vector
```
Set an element in the array to the provided key/index.


------
##### prepend
```php
public function prepend(...$values) : sqonk\phext\datakit\Vector
```
Add one or more elements to the start of the vector. If a constraint is set then excess elements will be removed from the end.


------
##### fill
```php
public function fill(int $amount, callable $callback) : sqonk\phext\datakit\Vector
```
Add a value supplied by the callback to the end of the vector a set number of times.

The callback should take no parameters.


------
##### prefill
```php
public function prefill(int $amount, callable $callback) : sqonk\phext\datakit\Vector
```
Add a value supplied by the callback to the start of the vector a set number of times.

The callback should take no parameters.


------
##### get
```php
public function get($key, $default = null) 
```
Return the value for a specified key. If the key is not present in the array then the default value is returned instead.

You may optionally pass a callback as the $key. When you do this the callback is used as a filter, where by the first item the callback returns `TRUE` for will be returned by the function as the found object.

Callback format: `myFunc($value, $index) -> bool`

**Returns:**  The found item or `NULL` if nothing was located for the key.


------
##### remove
```php
public function remove(...$keys) : sqonk\phext\datakit\Vector
```
Remove one or more elements from the vector.


------
##### remove_range
```php
public function remove_range(int $start, int $length) : sqonk\phext\datakit\Vector
```
Remove a range of values from the vector from the index at $start and extending for $length.

This method is primarily designed to work with sequential indexes but will also work with associative arrays by running the start and length through the extracted array keys.


------
##### clear
```php
public function clear() : sqonk\phext\datakit\Vector
```
Remove all elements from the array.


------
##### isset
```php
public function isset(...$keys) : bool
```
Returns `TRUE` if all the specified keys are present within the vector, `FALSE` otherwise.


------
##### keys
```php
public function keys() : sqonk\phext\datakit\Vector
```
Return all indexes of the array.


------
##### empty
```php
public function empty() : bool
```
Returns `TRUE` if there are 0 elements in the array, `FALSE` otherwise.


------
##### values
```php
public function values($key = null) : sqonk\phext\datakit\Vector
```
Return all values for a given key in the vector. This assumes all elements inside of the vector are an array or object.

If no key is provided then it will return all primary values in the vector.


------
##### unique
```php
public function unique($key = null) : sqonk\phext\datakit\Vector
```
Return a new vector containing all unique values in the current.

If $key is provided then the operation is performed on the values resulting from looking up $key on each element in the vector. This assumes all elements inside of the vector are an array or object.


------
##### frequency
```php
public function frequency($key = null) : sqonk\phext\datakit\Vector
```
Produces a new vector containing counts for the number of times each value occurs in the array.

If $key is provided then the operation is performed on the values resulting from looking up $key on each element in the vector, assuming all elements inside of the vector are an array or object.


------
##### prune
```php
public function prune($empties = '') : sqonk\phext\datakit\Vector
```
Remove all entries where the values corresponding to 'empties' are omitted.


------
##### first
```php
public function first() 
```
Return the first object in the array or null if array is empty.


------
##### last
```php
public function last() 
```
Return the last object in the array or null if array is empty.


------
##### middle
```php
public function middle(bool $weightedToFront = true) 
```
Return the object closest to the middle of the array.

- If the array is empty, returns `NULL`.

- If the array has less than 3 items, then return the first or last item depending on the value of $weightedToFront.

- Otherwise return the object closest to the centre. When dealing with arrays containing an even number of items then it will use the value of $weightedToFront to determine if it picks the item closer to the start or closer to the end.

- **$array** The array containing the items.
- **$weightedToFront** `TRUE` to favour centre items closer to the start of the array and `FALSE` to prefer items closer to the end.


------
##### choose
```php
public function choose() 
```
Randomly choose and return an item from the vector.


------
##### occurs_in
```php
public function occurs_in(string $heystack) : string|bool
```
Returns the first item in the vector found in the heystack or `FALSE` if none are found.


------
##### any
```php
public function any($match, bool $strict = false) : bool
```
Returns `TRUE` if any of the values within the vector are equal to the value provided, `FALSE` otherwise.

A callback may be provided as the match to perform more complex testing.

Callback format: `myFunc($value) -> bool`

For basic (non-callback) matches, setting $strict to `TRUE` will enforce type-safe comparisons.


------
##### all
```php
public function all($match, bool $strict = false) : bool
```
Returns `TRUE` if all of the values within the vector are equal to the value provided, `FALSE` otherwise.

A callback may be provided as the match to perform more complex testing.

Callback format: `myFunc($value) -> bool`

For basic (non-callback) matches, setting $strict to `TRUE` will enforce type-safe comparisons.


------
##### filter
```php
public function filter(callable $callback) : sqonk\phext\datakit\Vector
```
Filter the contents of the vector using the provided callback.

`ARRAY_FILTER_USE_BOTH` is provided as the flag to array_filter() so that your callback may optionally take the key as the second parameter.


------
##### intersect
```php
public function intersect(...$otherArrays) : sqonk\phext\datakit\Vector
```
Filter the vector based on the contents of one or more vectors or arrays and return a new vector containing just the elements that were deemed to exist in all.


------
##### diff
```php
public function diff(...$otherArrays) : sqonk\phext\datakit\Vector
```
Filter the vector based on the contents of one or more arrays and return a new vector containing just the elements that were deemed not to be present in all.


------
##### only_keys
```php
public function only_keys(...$keys) : sqonk\phext\datakit\Vector
```
Return a copy of the vector containing only the values for the specified keys, with index association being maintained.

This method is primarily designed for non-sequential vectors but can also be used with sequential 2-dimensional vectors. If the vector is sequential and the elements contained within are arrays or vectors then the operation is performed on them, otherwise it is performed on the top level of the vector.

It should be noted that if a key is not  present in the current vector then it will not be present in the resulting vector.


------
##### contains
```php
public function contains($needle) : bool
```
Search the array for the given needle (subject). This function is an alias of Vector::any().


------
##### ends_with
```php
public function ends_with($needle) : bool
```
Determines if the array ends with the needle.


------
##### starts_with
```php
public function starts_with($needle) : bool
```
Determines if the array starts with the needle.


------
##### trim
```php
public function trim() : sqonk\phext\datakit\Vector
```
Trim all entries in the array (assumes all entries are strings).


------
##### implode
```php
public function implode(string $delimier = '', string $subDelimiter = '') : string
```
Join all elements in the vector into a string using the supplied delimier as the separator.

This assumes all elements in the vector are capable of being cast to a string.


------
##### implode_only
```php
public function implode_only(string $delimier, array $keys, string $subDelimiter = '') : string
```
Implode the vector using the desired delimiter and sub-delimiter.

This method is primarily intended for non-sequential/associative vectors and differs from the standard implode in that it will only implode the values associated with the specified keys/indexes.


------
##### map
```php
public function map(callable $callback) : sqonk\phext\datakit\Vector
```
Apply a callback function to the vector. This version will optionally supply the corresponding index/key of the value when needed.

Callback format: `myFunc($value, $index) -> mixed`


------
##### chunk
```php
public function chunk(int $itemsPerBatch) : sqonk\phext\datakit\Vector
```
Split the array into batches each containing a total specified by $itemsPerBatch.

The final batch may contain less than the specified batch count if the array total does not divide evenly.


------
##### pad
```php
public function pad(int $count, $value) : sqonk\phext\datakit\Vector
```
Pad vector to the specified length with a value. If $count is positive then the array is padded on the right, if it's negative then on the left. If the absolute value of $count is less than or equal to the length of the array then no padding takes place.


------
##### pop
```php
public function pop(int $amount = 1, bool $returnRemoved = false) : sqonk\phext\datakit\Vector
```
Shorten the vector by removing elements off the end of the array to the number specified in $amount. If $returnRemoved is `TRUE` then the items removed will be returned, otherwise it returns a reference to itself for chaining purposes.


------
##### shift
```php
public function shift(int $amount = 1, bool $returnRemoved = false) : sqonk\phext\datakit\Vector
```
Modify the vector by removing elements off the beginning of the array to the number specified in $amount and return a vector containing the items removed. If $returnRemoved is `TRUE` then the items removed will be returned, otherwise it returns a reference to itself for chaining purposes


------
##### transpose
```php
public function transpose(string $groupKey, array $mergeMap) : sqonk\phext\datakit\Vector
```
Transform a set of rows and columns with vertical data into a horizontal configuration where the resulting array contains a column for each different value for the given fields in the merge map (associative array).

The group key is used to specify which field in the array will be used to flatten multiple rows into one.

For example, if you had a result set that contained a 'type' field, a corresponding 'reading' field and a 'time' field (used as the group key) then this method would merge all rows containing the same time value into a matrix containing as many columns as there are differing values for the type field, with each column containing the corresponding value from the 'reading' field.


------
##### groupby
```php
public function groupby($keys, bool $keepEmptyKeys = false) : sqonk\phext\datakit\Vector
```
Transform the vector (assuming it is a flat array of elements) and split them into a tree of vectors based on the keys passed in.

The vector will be re-sorted by the same order as the set of keys being used. If only one key is required to split the array then a singular string may be provided, otherwise pass in an array.

Unless $keepEmptyKeys is set to `TRUE` then any key values that are empty will be omitted.


------
##### splitby
```php
public function splitby(callable $callback) : sqonk\phext\datakit\Vector
```
Split the vector into a series of vectors based the varying results returned from a supplied callback.

This method differs from `groupby` in that it does not care about the underlying elements within the vector and relies solely on the callback to determine how the elements are divided up, where as `groupby` is explicitly designed to work with a vector of objects or entities that respond to key lookups. Further to this, `groupby` can produce a tree structure of nested vectors where as `splitby` will only ever produce one level.

The values returned from the callback must be capable of being used as an array key (e.g. strings, numbers). This is done by a `var_is_stringable` check. `NULL` values are allowed but used to omit the associated item from any of the sets.

- **$callback** A callback method that will produce the varying results used to sort each element into its own set.

Callback format: `myFunc($value, $index) -> mixed`


**Throws:**  UnexpectedValueException If the value returned from the callback is not capable of being used as an array key.

**Returns:**  A vector of vectors, one each for each different result returned from the callback.


------
##### sort
```php
public function sort(bool $dir = ASCENDING, int $flags = SORT_REGULAR) : sqonk\phext\datakit\Vector
```
Sort the vector in either `ASCENDING` or `DESCENDING` direction. If the vector is associative then index association is maintained, otherwise new indexes are generated.

Refer to the PHP documentation for all possible values on the $flags.


------
##### ksort
```php
public function ksort(bool $dir = ASCENDING, int $flags = SORT_REGULAR) : sqonk\phext\datakit\Vector
```
Sort the vector by the indexes in either `ASCENDING` or `DESCENDING` direction.

Refer to the PHP documentation for all possible values on the $flags.


------
##### keyed_sort
```php
public function keyed_sort($key) : sqonk\phext\datakit\Vector
```
Sort the vector based on the value of a key inside of the sub-array/object.

$key can be a singular string, specifying one key, or an array of keys.

If the vector is associative then index association is maintained, otherwise new indexes are generated.

NOTE: This method is designed for multi-dimensional vectors or vectors of objects.

See ksort for sorting the vector based on the array indexes.


------
##### shuffle
```php
public function shuffle() : sqonk\phext\datakit\Vector
```
Randomise the elements within the vector.


------
##### rotate_back
```php
public function rotate_back() : sqonk\phext\datakit\Vector
```
Treat the vector as a rotary collection and move each item back one place in order. The item at the end will be moved to the front.

This method is designed for sequential arrays, indexes are not preserved.


------
##### rotate_right
```php
public function rotate_right() : sqonk\phext\datakit\Vector
```
Alias of rotate_back()


------
##### rotate_forward
```php
public function rotate_forward() : sqonk\phext\datakit\Vector
```
Treat the vector as a rotary collection and move each item forward one place in order. The item at the front will be moved to the end.

This method is designed for sequential arrays, indexes are not preserved.


------
##### rotate_left
```php
public function rotate_left() : sqonk\phext\datakit\Vector
```
Alias of rotate_forward()


------
##### head
```php
public function head(int $count) : sqonk\phext\datakit\Vector
```
Return a copy of the vector only containing the number of rows from the start as specified by $count.


------
##### tail
```php
public function tail(int $count) : sqonk\phext\datakit\Vector
```
Return a copy of the vector only containing the number of rows from the end as specified by $count.


------
##### slice
```php
public function slice(int $start, int $length = null) : sqonk\phext\datakit\Vector
```
Return a copy of the vector only containing the the rows starting from $start through to the given length.


------
##### sample
```php
public function sample(int $minimum, int $maximum = null) : sqonk\phext\datakit\Vector
```
Return a copy of the vector containing a random subset of the elements. The minimum and maximum values can be supplied to focus the random sample to a more constrained subset.


------
##### clip
```php
public function clip($lower, $upper, bool $inplace = false) 
```
Provide a maximum or minimum (or both) constraint for the values in the vector.

If a value exceeds that constraint then it will be set to the constraint.

If either the lower or upper constraint is not needed then passing in null will ignore it.

If $inPlace is `TRUE` then this operation modifies this vector otherwise a copy is returned.


------
##### reverse
```php
public function reverse(bool $inplace = false) : sqonk\phext\datakit\Vector
```
Reverse the current order of the values within the vector. If $inplace is `TRUE` then this method will modify the existing vector instead of returning a copy.


------
##### flip
```php
public function flip(bool $inplace = false) : sqonk\phext\datakit\Vector
```
Swap the keys and values within the vector. If $inplace is `TRUE` then this method will modify the existing vector instead of returning a copy.


------
##### sum
```php
public function sum() : int|float|null
```
Compute a sum of the values within the vector.


------
##### avg
```php
public function avg() : int|float|null
```
Compute the average of the values within the vector.


------
##### max
```php
public function max() : int|float|null
```
Return the maximum value present within the vector.


------
##### min
```php
public function min() : int|float|null
```
Return the minimum value present within the vector.


------
##### median
```php
public function median() : int|float|null
```
Find the median value within the vector.


------
##### product
```php
public function product() : int|float|null
```
Compute the product of the values within the vector.


------
##### cumsum
```php
public function cumsum() : sqonk\phext\datakit\Vector
```
Compute a cumulative sum of the values within the vector.


------
##### cummax
```php
public function cummax() : sqonk\phext\datakit\Vector
```
Compute the cumulative maximum value within the vector.


------
##### cummin
```php
public function cummin() : sqonk\phext\datakit\Vector
```
Compute the cumulative minimum value within the vector.


------
##### cumproduct
```php
public function cumproduct() : sqonk\phext\datakit\Vector
```
Compute the cumulative product of the values within the vector.


------
##### variance
```php
public function variance() : sqonk\phext\datakit\nul|int|float
```
Compute the variance of values within the vector.


------
##### reduce
```php
public function reduce(callable $callback, $initial = null) : mixed
```
Iteratively reduce the vector to a single value using a callback function.

If the optional $initial is available, it will be used at the beginning of the process, or as a final result in case the vector is empty.

Callback format: `myFunc( $carry, $item ) : mixed`

Returns the resulting value.


------
##### normalise
```php
public function normalise() : sqonk\phext\datakit\Vector
```
Normalise the vector to a range between 0 and 1.

This method expects the contents of the vector to be numerical. You will need to filter any invalid values prior to running the normalisation.


------
##### normalize
```php
public function normalize() : sqonk\phext\datakit\Vector
```
Alias of self::normalise().


------
##### round
```php
public function round(int $precision, int $mode = PHP_ROUND_HALF_UP) : sqonk\phext\datakit\Vector
```
Round all values in the vector up or down to the given decimal point precision.


------
