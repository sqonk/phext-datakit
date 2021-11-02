###### PHEXT > [DataKit](../README.md) > [API Reference](index.md) > CSV
------
### CSV
The CSV class can be used for producing CSV documents. It abstracts the mechanics of producing the file format, allowing your code to focus on its own logic.

Under the hood it relies on `fputcsv` for outputting rows.

It is designed for exporting data as productively as possible, the system for which follows a configure-then-execute pattern. By setting up a field map, which creates a relationship between the human-readable column headers and the array keys for eventual data that is provided to it, all rows can be passed in at once or as required.

This class works in real-time, meaning that the data is written out to the stream as you pass it in. It is also stringable, allowing it to be used in many standard forms of output that can work with strings.
#### Methods
[__construct](#__construct)
[__destruct](#__destruct)
[map](#map)
[set_map](#set_map)
[add_map_pair](#add_map_pair)
[headers](#headers)
[set_headers](#set_headers)
[add_raw_row](#add_raw_row)
[add_record](#add_record)
[add_records](#add_records)
[__tostring](#__tostring)

------
##### __construct
```php
public function __construct(string $path = null) 
```
Create a new CSV Exporter.

- **$path** A path to the output file that will be used to generate the CSV. If set to ``NULL`` the CSV will be produced directly in memory. Defaults to ``NULL``.


------
##### __destruct
```php
public function __destruct() 
```
No documentation available.


------
##### map
```php
public function map() : array
```
Return the current header-to-key map.


------
##### set_map
```php
public function set_map(array $fieldMap) : bool
```
Set a map for the exporter, which is series of column headers and array keys that will be used to automatically build the CSV from one or more objects or associative arrays passed into the class at a later stage.

- **$fieldMap** An associative array where the column headers are the array keys and
the values are the array values.

Will trigger a warning if called after the headers have already been output.

**Returns:**  `TRUE` if the field map was successfully set, `FALSE` otherwise.


------
##### add_map_pair
```php
public function add_map_pair(string $header, string $key) : bool
```
Map a column header to a set array key that will be used to acquire the corresponding value from each record.

- **$header** The column header.
- **$key** The corresponding key for accessing the value within a record.

Will trigger a warning if called after the headers have already been output.

**Returns:**  `TRUE` if the map header pair were successfully set, `FALSE` otherwise.


------
##### headers
```php
public function headers() : array
```
Return the current set of human-readable column headers.


------
##### set_headers
```php
public function set_headers(array $headers) : bool
```
Set the column headers for the exporter.

NOTE: If you have previously set a map by calling `set_map()` then the headers are automatically extrapolated from it. You do not need to call this method unless you are bypassing the use of field maps and records.

- **$headers** A sequential array of strings representing the column headers.

Will trigger a warning if called after the headers have already been output. Will also trigger a notice if a field map has previously been set.

**Returns:**  `TRUE` if the headers were successfully set, `FALSE` otherwise.


------
##### add_raw_row
```php
public function add_raw_row(array $row) : sqonk\phext\datakit\CSV
```
Add a series of values as the next row in the CSV.

- **$row** A sequential array of the values corresponding the order of the column headers.

**Returns:**  The CSV object.


------
##### add_record
```php
public function add_record($record) : sqonk\phext\datakit\CSV
```
Add a single record to the CSV. This method differs from `add_raw_row()` in that the provided array or object should be associative where the keys correspond to the column headers.

- **array|ArrayAccess** $record An associative array or object containing the row of data.


**Throws:**  RuntimeException If no field map has been set. 
**Throws:**  InvalidArgumentException If the provided record is not of the correct type.

**Returns:**  The CSV object.


------
##### add_records
```php
public function add_records($records) : sqonk\phext\datakit\CSV
```
Add multiple records to the CSV.

- **array|ArrayAccess** $records The array of records to add.


**Throws:**  InvalidArgumentException If $records is not of the correct type.


**See:**  add_record() for other possible exceptions that may be thrown.

**Returns:**  The CSV object.


------
##### __tostring
```php
public function __tostring() : string
```
Convert the CSV in its current state to a string.


------
