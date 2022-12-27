###### PHEXT > [DataKit](../README.md) > [API Reference](index.md) > CSVImporter
------
### CSVImporter
The CSVImporter is designed to efficiently load or parse CSV documents. It is the underlying engine used by the static methods in the Importer class.

@implements \Iterator<mixed, mixed>
#### Methods
- [init](#init)
- [__construct](#__construct)
- [__destruct](#__destruct)
- [close](#close)
- [delimiter](#delimiter)
- [enclosedBy](#enclosedby)
- [line_ending](#line_ending)
- [headers_first_row](#headers_first_row)
- [skip](#skip)
- [custom_headers](#custom_headers)
- [next_row](#next_row)
- [validate](#validate)
- [reset](#reset)
- [headers](#headers)
- [all_remaining](#all_remaining)
- [all](#all)
- [row_index](#row_index)
- [next](#next)
- [current](#current)
- [key](#key)
- [rewind](#rewind)
- [valid](#valid)

------
##### init
```php
static public function init(string $input, bool $inputIsRawData = false, bool $headersAreFirstRow = false, array $customHeaders = null, int $skipRows = 0, string $delimiter = ',', string $enclosedBy = '"', string $lineEnding = '\n') : sqonk\phext\datakit\CSVImporter
```
Initialise a new CSVImporter. Use this static method if you wish to chain a sequence of calls in one line.

- **string** $input Either the file path of the CSV Document or the raw CSV text. @see $inputIsRawData.
- **bool** $inputIsRawData When ``TRUE``, the `$input` parameter is interpreted as containing the CSV data. When `FALSE` it is assumed to be the file path to the relevant CSV document.
- **bool** $headersAreFirstRow When `TRUE` the first row of the CSV document is assigned as the headers, which are the resulting keys in the associative array produced for each row that is read in. Defaults to ``FALSE``.
- **list<string>** $customHeaders Assigns the given array as the headers for the import, which are the resulting keys in the associative array produced for each row that is read in. If this is set and $headersAreFirstRow is set to ``TRUE`` then the custom headers will override it, however the first row will still be skipped over.
- **int** $skipRows Additionally skip over the given number or rows before reading begins.
- **non-empty-string** $delimiter Set the field delimiter (one single-byte character only).
- **string** $enclosedBy Set the field enclosure character (one single-byte character only).
- **non-empty-string** $lineEnding Set character sequence that denotes the end of a line (row).


------
##### __construct
```php
public function __construct(string $input, bool $inputIsRawData = false, bool $headersAreFirstRow = false, array $customHeaders = null, int $skipRows = 0, string $delimiter = ',', string $enclosedBy = '"', string $lineEnding = '\n') 
```
Initialise a new CSVImporter.

- **string** $input Either the file path of the CSV Document or the raw CSV text. @see $inputIsRawData.
- **bool** $inputIsRawData When ``TRUE``, the `$input` parameter is interpreted as containing the CSV data. When `FALSE` it is assumed to be the file path to the relevant CSV document.
- **bool** $headersAreFirstRow When `TRUE` the first row of the CSV document is assigned as the headers, which are the resulting keys in the associative array produced for each row that is read in. Defaults to ``FALSE``.
- **list<string>** $customHeaders Assigns the given array as the headers for the import, which are the resulting keys in the associative array produced for each row that is read in. If this is set and $headersAreFirstRow is set to ``TRUE`` then the custom headers will override it, however the first row will still be skipped over.
- **int** $skipRows Additionally skip over the given number or rows before reading begins.
- **non-empty-string** $delimiter Set the field delimiter (one single-byte character only).
- **string** $enclosedBy Set the field enclosure character (one single-byte character only).
- **non-empty-string** $lineEnding Set character sequence that denotes the end of a line (row).


------
##### __destruct
```php
public function __destruct() 
```
No documentation available.


------
##### close
```php
public function close() : void
```
Close off access to the underlying file resource, if one is open. Repeated calls to this, or if the input source is a raw string, will do nothing.


------
##### delimiter
```php
public function delimiter(string $separatedBy) : self
```
Set the field delimiter (one single-byte character only).

- **non-empty-string** $separatedBy A non-empty string used as a token to split the text of each line into seperate elements.


------
##### enclosedBy
```php
public function enclosedBy(string $enclosure) : self
```
Set the field enclosure character (one single-byte character only).


------
##### line_ending
```php
public function line_ending(string $lineEnding) : self
```
Set character sequence that denotes the end of a line (row).

- **non-empty-string** $lineEnding The character(s) that denote the line ending for the target CSV file.


------
##### headers_first_row
```php
public function headers_first_row(bool $headersPresent) : self
```
When ``TRUE``, the `$input` parameter is interpreted as containing the CSV data. When `FALSE` it is assumed to be the file path to the relevant CSV document.


------
##### skip
```php
public function skip(int $rows) : self
```
Additionally skip over the given number or rows before reading begins. This method has no effect once reading has begun, unless the importer is reset.

If the given value exceeds the number of rows in the CSV then the importer will raise an E_USER_WARNING at the time of internal initialisation.


**See:**  reset


------
##### custom_headers
```php
public function custom_headers(array $headers) : self
```
Assigns the given array as the headers for the import, which are the resulting keys in the associative array produced for each row that is read in. If this is set and $headersAreFirstRow is set to ``TRUE`` then the custom headers will override it, however the first row will still be skipped over.

- **list<string>** $headers The custom headers to assign.


------
##### next_row
```php
public function next_row() : array|bool
```
Advance the importer by one line and return the resulting row of fields.

**Returns:**  array<string, string>|bool An associative array containing the decoded fields that were read in or `FALSE` if the end of the CSV was reached.


------
##### validate
```php
public function validate() : bool
```
Preflight the importer by running the initial internal setup and verifying that it completed without error.

**Returns:**  bool ``TRUE`` if no problems were encountered, ``FALSE`` otherwise.


------
##### reset
```php
public function reset() : void
```
Reset the importer so the next reads starts from the top of the file. All steps, including internal initialisation are taken again.


------
##### headers
```php
public function headers() : ?array
```
Return the calculated set of headers.

**Returns:**  ?list<string> The header array, or `NULL` if the importer has not yet been initialised or failed to initialise.


------
##### all_remaining
```php
public function all_remaining() : array
```
Return all remaining rows yet to be read in from the document.

**Returns:**  list<array<string, string>> An array containing every row that was read in.


------
##### all
```php
public function all() : array
```
Return all rows contained within the CSV document. If reading has already commenced then the importer is first reset.

**Returns:**  list<array<string, string>> An array containing every row that was read in.


------
##### row_index
```php
public function row_index() : int
```
The index correlating to the position in the CSV document the importer is currently up to. It represents the total amount of lines that have been read in.

The initial index is 0.

This directly relates in a 1:1 fashion with the original CSV document. i.e. if the headers are in the first row and 1 row been read then the row index will be at 2.


------
##### next
```php
public function next() : void
```
Iterator implementation method. Advances the importer to the next line, reading in the next row.


------
##### current
```php
public function current() : mixed
```
Iterator implementation method. Returns the current row. Will advance the reader by one line if reading has not yet begun.


------
##### key
```php
public function key() : mixed
```
Iterator implementation method. Returns the current row count.


------
##### rewind
```php
public function rewind() : void
```
Iterator implementation method. Calls reset().


------
##### valid
```php
public function valid() : bool
```
Iterator implementation method.


------
