###### PHEXT > [DataKit](../README.md) > [API Reference](index.md) > GroupedDataFrame
------
### GroupedDataFrame
The GroupedDataFrame is a special class that manages a group of normal DataFrame objects. Normal actions on the DataFrame can be called and actioned against all objects within the set.

This class is used internally by DataFrame and you should not need to instantiate it yourself under most conditions.

@implements \IteratorAggregate<mixed, list<array<string, string>>> @implements \ArrayAccess<mixed, list<array<string, string>>>
#### Methods
- [getIterator](#getiterator)
- [offsetSet](#offsetset)
- [offsetExists](#offsetexists)
- [offsetUnset](#offsetunset)
- [offsetGet](#offsetget)
- [count](#count)
- [__construct](#__construct)
- [__call](#__call)
- [__get](#__get)
- [__tostring](#__tostring)
- [combine](#combine)
- [export](#export)

------
##### getIterator
```php
public function getIterator() : Iterator
```
No documentation available.


------
##### offsetSet
```php
public function offsetSet(mixed $index, mixed $dataFrame) : void
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
##### offsetGet
```php
public function offsetGet(mixed $index) : mixed
```
No documentation available.


------
##### count
```php
public function count() : int
```
No documentation available.


------
##### __construct
```php
public function __construct(array $groups, string $groupedColumn) 
```
Construct a new GroupedDataFrame containing multiple DataFrame objects.

- **array<mixed,** DataFrame> $groups Array of standard DataFrame objects.
- **string** $groupedColumn The singular DataFrame column that was used to split the original frame into the group.


------
##### __call
```php
public function __call(string $name, array $args) : mixed
```
@param string $name @param array<mixed> $args


------
##### __get
```php
public function __get(mixed $key) : mixed
```
No documentation available.


------
##### __tostring
```php
public function __tostring() : string
```
No documentation available.


------
##### combine
```php
public function combine(bool $keepIndexes = true) : sqonk\phext\datakit\DataFrame
```
Combine all frames within the group back into a singular DataFrame.

If $keepIndexes is set to true then all existing indexes are kept and merged. Keep in mind that you may suffer data overwrite if one or more of the frames in the set have matching indexes.

- **bool** $keepIndexes  When set to `FALSE` then the new DataFrame reindexes all rows with a standard numerical sequence starting from 0.

**Returns:**  DataFrame the new combined DataFrame.


------
##### export
```php
public function export(string $dir = '.', array $columns = null, string $delimiter = ',') : ?array
```
Functional map to the standard export within DataFrame.

- **string** $dir Path to the directory/folder to export the CSV to. Defaults to the current working directory.
- **list<string>** $columns Which columns to export.
- **string** $delimiter CSV delimiter.

**Returns:**  ?list<string>


------
