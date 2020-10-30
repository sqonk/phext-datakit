###### PHEXT > [DataKit](../README.md) > [API Reference](index.md) > GroupedDataFrame
------
### GroupedDataFrame
The GroupedDataFrame is a special class that manages a group of normal DataFrame objects. Normal actions on the DataFrame can be called and actioned against all objects within the set.

This class is used internally by DataFrame and you should not need to instanciate it yourself under most conditions.
#### Methods
[getIterator](#getiterator)
[offsetSet](#offsetset)
[offsetExists](#offsetexists)
[offsetUnset](#offsetunset)
[offsetGet](#offsetget)
[count](#count)
[__construct](#__construct)
[__call](#__call)
[__get](#__get)
[__toString](#__tostring)
[combine](#combine)
[export](#export)

------
##### getIterator
```php
public function getIterator() 
```
No documentation available.


------
##### offsetSet
```php
public function offsetSet($index, $dataFrame) 
```
No documentation available.


------
##### offsetExists
```php
public function offsetExists($index) 
```
No documentation available.


------
##### offsetUnset
```php
public function offsetUnset($index) 
```
No documentation available.


------
##### offsetGet
```php
public function offsetGet($index) 
```
No documentation available.


------
##### count
```php
public function count() 
```
No documentation available.


------
##### __construct
```php
public function __construct(array $groups, string $groupedColumn) 
```
Construct a new GroupedDataFrame containing multiple DataFrame objects.

- **$groups** Array of standard DataFrame objects.
- **$groupedColumn** The singular DataFrame column that was used to split the original frame into the group.


------
##### __call
```php
public function __call(string $name, array $args) 
```
No documentation available.


------
##### __get
```php
public function __get($key) 
```
No documentation available.


------
##### __toString
```php
public function __toString() : string
```
No documentation available.


------
##### combine
```php
public function combine(bool $keepIndexes = true) 
```
Combine all frames within the group back into a singular DataFrame.

If $keepIndexes is set to true then all existing indexes are kept and merged. Keep in mind that you may suffer data overwrite if one or more of the frames in the set have matching indexes.

- **$keepIndexes**  When set to `FALSE` then the new DataFrame reindexes all rows with a standard numerical sequence starting from 0.

**Returns:**  the new combined DataFrame.


------
##### export
```php
public function export($dir = '.', array $columns = null, string $delimeter = ',') 
```
Functional map to the standard export within DataFrame.

- **$dir** Path to the directory/folder to export the CSV to.
- **$columns** Which columns to export.
- **$delimeter** CSV delimiter.


------
