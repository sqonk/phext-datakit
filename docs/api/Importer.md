###### PHEXT > [DataKit](../README.md) > [API Reference](index.md) > Importer
------
### Importer
A selection of routines for importing data from various static sources such as files.
#### Methods
[csv_data](#csv_data)
[csv_file](#csv_file)
[yield_csv](#yield_csv)
[csv_dataframe](#csv_dataframe)
[delimitered_data](#delimitered_data)

------
##### csv_data
```php
static public function csv_data(callable $callback, string $data, bool $headersAreFirstRow = false, array $customHeaders = null) 
```
Import a CSV from a string containing the data.

Your callback method should be in the format of:

`function myCSVCallback($row)`

where $row is an array of the values retrieved from the current row in the CSV. When the first row is indicated as containing the column headers then the supplied array will be indexed with the column headers as the keys.

In the cases were the CSV has no column headers then the supplied array will be in simple sequential order.

- **$callback** A callback method to process each row. Pass in `NULL` to have the data returned at the end.
- **$data** The CSV data in string format.
- **$headersAreFirstRow** `TRUE` or `FALSE`, where are not the first row contains headers.
- **$customHeaders** A custom set of column headers to override any existing or absent headers.

**Returns:**  `TRUE` upon successful completion or the imported data array when no callback is being used.


------
##### csv_file
```php
static public function csv_file(callable $callback, string $filePath, bool $headersAreFirstRow = false, array $customHeaders = null, int $skipRows = 0) 
```
Import a CSV from a local file on disk or a URL.

Provides a fast and convienient way of importing data from CSV formats. Each row is returned to your callback method as an array of values, where you may do as you desire with it. Alternatively if you pass in `NULL` as the callback then all the data will be returned as an array.

Your callback method should be in the format of:

`function myCSVCallback($row)`

where $row is an array of the values retrieved from the current row in the CSV. When the first row is indicated as containing the column headers then the supplied array will be indexed with the column headers as the keys.

In the cases were the CSV has no column headers then the supplied array will be in simple sequential order.

- **$callback** A callback method to process each row. Pass in `NULL` to have the data returned at the end.
- **$filePath** Path or URL to the file.
- **$headersAreFirstRow** `TRUE` or `FALSE`, where are not the first row contains headers.
- **$customHeaders** A custom set of column headers to override any existing or absent headers.
- **$skipRows** Skip over a specified number of rows at the start. Defaults to 0.

**Returns:**  `TRUE` upon successful completion or the imported data array when no callback is being used.

This method will throw an exception if an error is encountered at any point in the process.


------
##### yield_csv
```php
static public function yield_csv(string $filePath, bool $headersAreFirstRow = false, array $customHeaders = null, int $skipRows = 0) 
```
Import a CSV from a local file on disk or a URL and yield one row at a time as a generator to an outer loop.

Each yielded row is an array of the values retrieved from the current row in the CSV. When the first row is indicated as containing the column headers then the supplied array will be indexed with the column headers as the keys.

In the cases were the CSV has no column headers then the supplied array will be in simple sequential order.

- **$filePath** Path or URL to the file.
- **$headersAreFirstRow** `TRUE` or `FALSE`, where are not the first row contains headers.
- **$customHeaders** A custom set of column headers to override any existing or absent headers.
- **$skipRows** Skip over a specified number of rows at the start. Defaults to 0.

**Returns:**  A generator for use in a foreach loop.

This method will throw an exception if an error is encountered at any point in the process.


------
##### csv_dataframe
```php
static public function csv_dataframe(string $filePath, $columns = false, int $skipRows = 0) 
```
Import a CSV directly into a DataFrame object in the most memory efficient way.

In the cases were the CSV has no column headers then the supplied array will be in simple sequential order.

- **$filePath** Path or URL to the CSV file.
- **$columns** When `TRUE`, will take the first row as the headers. When an array is supplied then the array will be used as the column. Passing `FALSE` or any other value will result in sequential column headers.
- **$skipRows** Skip over a specified number of rows at the start. Defaults to 0.

This method will throw an exception if an error is encountered at any point in the process.

**Returns:**  A DataFrame object containing the rows from the CSV, or `NULL` if no rows were retrieved.


------
##### delimitered_data
```php
static public function delimitered_data(callable $callback, string $data, string $itemDelimiter, string $lineDelimiter = '\n', bool $headersAreFirstRow = false, $customHeaders = null) 
```
Split a string of raw data down into rows and columns.

Each row is returned to your callback method as an array of values, where you may do as you desire with it.

Your callback method should be in the format of:

`function myCallback($row)`

where $row is an array of the values retrieved from the current row or line in the data. The supplied array will be in simple sequential order.

- **$callback** A callback method to process each row.
- **$data** The data to be processed.
- **$itemDelimiter** The token used to split each row into individual items.
- **$lineDelimiter** The line ending used to split the data into seperate rows or lines.
- **$headersAreFirstRow** `TRUE` or `FALSE`, where are not the first row contains headers.
- **$customHeaders** A custom set of column headers to override any existing or absent headers.

**Returns:**  `TRUE` upon successful completion or the compiled data array when not using a callback.

This method will throw an exception if an error is encountered at any point in the process or the provided data can not be broken down into lines using the provided line ending character.


------
