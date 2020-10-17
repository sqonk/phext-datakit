###### PHEXT > [DataKit](../README.md) > [API Reference](index.md) > DataFrame
------
### DataFrame
The DataFrame is a class inspired by, and loosely based off of, a class by the same name from the Pandas library in Python. It specialises in working with 2 dimensional arrays (rows and columns) that may originate from data sources such as CSV files or data fetched from a relational database.

Various basic statistical and mathematical functions are provided as well numerous methods for transforming and manipulating the underlying data and presentation thereof.

Adheres to interfaces: Stringable, ArrayAccess, Countable, IteratorAggregate
#### Methods
[make](#make)
[empty_frames](#empty_frames)
[getIterator](#getiterator)
[offsetSet](#offsetset)
[offsetExists](#offsetexists)
[offsetUnset](#offsetunset)
[offsetGet](#offsetget)
[__toString](#__tostring)
[__construct](#__construct)
[copy](#copy)
[clone](#clone)
[display_headers](#display_headers)
[display_generic_indexes](#display_generic_indexes)
[index](#index)
[transformers](#transformers)
[column_is_numeric](#column_is_numeric)
[data](#data)
[flattened](#flattened)
[row](#row)
[indexes](#indexes)
[headers](#headers)
[head](#head)
[tail](#tail)
[slice](#slice)
[sample](#sample)
[change_header](#change_header)
[reindex_rows](#reindex_rows)
[reindex_rows_with_column](#reindex_rows_with_column)
[filter](#filter)
[unanfilter](#unanfilter)
[ufilter](#ufilter)
[sort](#sort)
[usort](#usort)
[shape](#shape)
[size](#size)
[count](#count)
[values](#values)
[report_data](#report_data)
[report](#report)
[print](#print)
[clip](#clip)
[prune](#prune)
[oob](#oob)
[oob_region](#oob_region)
[gaps](#gaps)
[frequency](#frequency)
[any](#any)
[all](#all)
[abs](#abs)
[std](#std)
[sum](#sum)
[avg](#avg)
[max](#max)
[min](#min)
[cumsum](#cumsum)
[cummax](#cummax)
[cummin](#cummin)
[cumproduct](#cumproduct)
[median](#median)
[product](#product)
[variance](#variance)
[normalise](#normalise)
[normalize](#normalize)
[quartile](#quartile)
[quantile](#quantile)
[round](#round)
[correlation](#correlation)
[corr](#corr)
[summary](#summary)
[groupby](#groupby)
[drop_columns](#drop_columns)
[drop_rows](#drop_rows)
[duplicated](#duplicated)
[drop_duplicates](#drop_duplicates)
[pivot](#pivot)
[depivot](#depivot)
[transpose](#transpose)
[transform](#transform)
[add_row](#add_row)
[add_column](#add_column)
[apply_display_transformer](#apply_display_transformer)
[plot](#plot)
[stock](#stock)
[box](#box)
[hist](#hist)
[export](#export)

------
##### make
```php
static public function make(array $data, array $headers = null) 
```
Static equivilent of `new DataFrame()`.


------
##### empty_frames
```php
static public function empty_frames() 
```
No documentation available.


------
##### getIterator
```php
public function getIterator() 
```
No documentation available.


------
##### offsetSet
```php
public function offsetSet($index, $row) 
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
##### __toString
```php
public function __toString() 
```
Converting the DataFrame to a string produces the report.

See: report()


------
##### __construct
```php
public function __construct(array $data = null, array $headers = null) 
```
Construct a new dataframe with the provided data. You may optionally provided the set of column headers in the second parameter. If you choose to do this then they should match the keys in the array.

NOTE: The provided array must have at least one element/row and must also be 2-dimensional in structure.


------
##### copy
```php
public function copy() 
```
Produce an exact replica of the dataframe.


------
##### clone
```php
public function clone($data, $headers = null) 
```
Produce a copy of the dataframe consisting of only the supplied data. All other information such as transfomers and header settings remain the same.


------
##### display_headers
```php
public function display_headers($display = null) 
```
Whether or not the DataFrame should display the column headers when it is printed. The default is `TRUE`.


------
##### display_generic_indexes
```php
public function display_generic_indexes($display = null) 
```
Whether or not the DataFrame should display the row indexes that sequentially numerical when it is printed.

The default is `TRUE`.

This is automatically disabled for pivoted DataFrames.


------
##### index
```php
public function index($indexHeader = null) 
```
Set or get the column header currently or to be used as the row indexes.

You should not need to set this.

See reindex_rows_with_column() instead.


------
##### transformers
```php
public function transformers($transformers = null) 
```
Used to set or get the full list of display transformers.

Used internally. You should not need to call this unction under normal circumstances.

See apply_display_transformer() instead.


------
##### column_is_numeric
```php
public function column_is_numeric($column) 
```
Returns `TRUE` if and only if all values within the given column ontain a valid number.


------
##### data
```php
public function data() 
```
Return the associative array containing all the data within the DataFrame.


------
##### flattened
```php
public function flattened(bool $includeIndex = true, ...$columns) 
```
Flatten the DataFrame into a native array.

- **$includeIndex:**  If `TRUE` then use the DataFrame indexes as the keys in the array.
- **$columns:**  One or more columns that should be used in the resulting array, all columns if null is supplied.

The columns can be supplied as a set of variable arguments or an array as the second argument.


------
##### row
```php
public function row($index) 
```
Return the row at $index.


------
##### indexes
```php
public function indexes() 
```
Return an array of all the current row indexes.


------
##### headers
```php
public function headers() 
```
All column headers currently in the DataFrame.


------
##### head
```php
public function head(int $count) 
```
Return a copy of the DataFrame only containing the number of rows from the start as specified by $count.


------
##### tail
```php
public function tail(int $count) 
```
Return a copy of the DataFrame only containing the number of rows from the end as specified by $count.


------
##### slice
```php
public function slice(int $start, int $length = null) 
```
Return a copy of the DataFrame only containing the the rows starting from $start through to the given length.


------
##### sample
```php
public function sample(int $minimum, int $maximum = null) 
```
Return a copy of the DataFrame containing a random subset of the rows. The minimum and maximum values can be supplied to focus the random sample to a more constrained subset.


------
##### change_header
```php
public function change_header(string $column, string $newName, bool $inPlace = false) 
```
Change the name of a column within the DataFrame. If $inPlace is `TRUE` then this operation modifies the receiver otherwise a copy is returned.


------
##### reindex_rows
```php
public function reindex_rows(array $labels, bool $inPlace = false) 
```
Reindex the DataFrame using the provided labels. If $inPlace is `TRUE` then this operation modifies the receiver otherwise a copy is returned.


------
##### reindex_rows_with_column
```php
public function reindex_rows_with_column(string $column, bool $inPlace = false) 
```
Push one of the columns out to become the row index. If $inPlace is `TRUE` then this operation modifies the receiver otherwise a copy is returned.


------
##### filter
```php
public function filter(callable $callback, ...$columns) 
```
Filter the DataFrame using the provided callback and one or more columns. If no columns are specified then the operation applies to all.

Callback format: `myFunc($value, $column, $rowIndex) -> bool`

For a row to make it into the filtered set then only ONE of the columns need to equate to true from the callback.


------
##### unanfilter
```php
public function unanfilter(callable $callback, ...$columns) 
```
Filter the DataFrame using the provided callback and one or more columns. If no columns are specified then the operation applies to all.

Callback format: `myFunc($value, $column, $rowIndex) -> bool`

For a row to make it into the filtered set then ALL of the columns need to equate to true from the callback.


------
##### ufilter
```php
public function ufilter(callable $callback) 
```
Filter the DataFrame using the provided callback and one or more columns. If no columns are specified then the operation applies to all.

Callback format: `myFunc($row, $rowIndex) -> bool`

This function differs from filter() and unanfilter() in that it passes the whole row to the callback. This is useful if your condition of inclusion requires cross comparing data across columns within the row.


------
##### sort
```php
public function sort(...$columns) 
```
Sort the DataFrame via one or more columns.

If the last parameter passed in is either `ASCENDING` or `DESCENDING` then it will determine the direction in which the dataframe is ordered. The default is `ASCENDING`.


------
##### usort
```php
public function usort(callable $callback, ...$columns) 
```
Sort the DataFrame using a callback and one or more columns.

Callback format: `myFunc($value1, $value2, $column) -> bool`


------
##### shape
```php
public function shape() 
```
Return an array containing both the number of rows and columns.


------
##### size
```php
public function size($column = null) 
```
If a column is specified then return the number of rows containing a value for it.

If no column is given then return a new DataFrame containing the counts for all columns.


------
##### count
```php
public function count() 
```
Return the number of rows.


------
##### values
```php
public function values($columns = null, bool $filterNAN = true) 
```
Return all values for the given column. If $filterNAN is `TRUE` then omit values that are `NULL`.


------
##### report_data
```php
public function report_data(...$columns) 
```
Return the data array with all values parsed by any registered transformers.

If you wish to output a report to something else other than the command line then this method will allow you to present the data as desired.


------
##### report
```php
public function report(...$columns) 
```
Produce a formatted string, suitable for outputing to the commandline or browser, detailing all rows and the desired columns. If no columns are specified then all columns are used.


------
##### print
```php
public function print(...$columns) 
```
Print to stdout the report for this DataFrame.

See: report()


------
##### clip
```php
public function clip($lower, $upper, string $column = null, bool $inplace = false) 
```
Provide a maximum or minimum (or both) constraint for the values on column.

If a row's value for the column exceeds that constraint then it will be set to the constraint.

If either the lower or upper constraint is not needed then passing in null will ignore it.

If no column is specified then the constraints apply to all columns.

If $inPlace is `TRUE` then this operation modifies the receiver otherwise a copy is returned.


------
##### prune
```php
public function prune($lower, $upper, $column = null, $inplace = false) 
```
Remove any rows where the value of the provided column exeeds the provided lower or upper boundary, for a given column.

If either the lower or upper constraint is not needed then passing in `NULL` will ignore it.

If no column is specified then the filter applies to all columns.

If $inPlace is `TRUE` then this operation modifies the receiver otherwise a copy is returned.


------
##### oob
```php
public function oob($lower, $upper, $column = null) 
```
Return a new DataFrame containing the rows where the values of the given column exceed a lower and/or upper boundary.

If either the lower or upper constraint is not needed then passing in `NULL` will ignore it.

If no column is specified then the filter applies to all columns.


------
##### oob_region
```php
public function oob_region($theshhold, $direction, string $column) 
```
Return a new 2-column DataFrame containing both the start and end points where values for a specific column exceed a given threshold.

$direction can be `OOB_LOWER`, `OOB_UPPER` or `OOB_ALL` to dertermining if the threshhold is calculated as a minimum boundary, maximum boundary or either.

Where `oob()` simply returns all the rows that exceed the threshold, this method will return a DataFrame of regions, where the start and end values refer to the row indexes of the current DataFrame.


------
##### gaps
```php
public function gaps($amount, string $usingColumn, string $resultColumn = '') 
```
Return a new 3-column DataFrame containing areas in the current where running values in a column exceed the given amount.

For example, if you have a column of timestamps and those timestamps typically increase by N minutes per row, then this method can be used to find possible missing rows where the jump in time is greater than the expected amount.

For every row where the given amount is exceeded, a row in the resulting DataFrame will exist where 'start' and 'end' list the values where the gap was found. A third column 'segments' details how many multiples of the amount exist between the values.

Providing a column name to $resultColumn allows you to perform the comparison in one column while filling the resulting DataFrame with referenced values from another column.


------
##### frequency
```php
public function frequency(string $column) 
```
Produces a new DataFrame containing counts for the number of times each value occurs in the given column.


------
##### any
```php
public function any($value, string $column = null) 
```
Returns `TRUE` if ANY of the rows for a given column match the given value.

If no column is specified then the the check runs over all columns.


------
##### all
```php
public function all($value, $column = null) 
```
Returns `TRUE` if ALL of the rows for a given column match the given value.

If no column is specified then the the check runs over all columns.


------
##### abs
```php
public function abs($column = null, $inplace = false) 
```
Convert all values in a given column to their absolute value.

If no column is specified then the the operation runs over all columns.

If $inPlace is `TRUE` then this operation modifies the current DataFrame, otherwise a copy is returned.


------
##### std
```php
public function std(bool $sample = false, ...$columns) 
```
Compute a standard deviation of one or more columns.

If no column is specified then the the operation runs over all columns.

If exactly one column is supplied then a single value is returned, otherwise a DataFrame of 1 value per column is produced.

$sample is passed through to the standard deviation calculation to determine how the result is producted.


------
##### sum
```php
public function sum(...$columns) 
```
Compute a sum of one or more columns.

If no column is specified then the the operation runs over all columns.

If exactly one column is supplied then a single value is returned, otherwise a DataFrame of 1 value per column is produced.


------
##### avg
```php
public function avg(...$columns) 
```
Compute the average of one or more columns.

If no column is specified then the the operation runs over all columns.

If exactly one column is supplied then a single value is returned, otherwise a DataFrame of 1 value per column is produced.


------
##### max
```php
public function max(...$columns) 
```
Return the maximum value present for one or more columns.

If no column is specified then the the operation runs over all columns.

If exactly one column is supplied then a single value is returned, otherwise a DataFrame of 1 value per column is produced.


------
##### min
```php
public function min(...$columns) 
```
Return the minimum value present for one or more columns.

If no column is specified then the the operation runs over all columns.

If exactly one column is supplied then a single value is returned, otherwise a DataFrame of 1 value per column is produced.


------
##### cumsum
```php
public function cumsum(...$columns) 
```
Compute a cumulative sum of one or more columns.

If no column is specified then the the operation runs over all columns.

If exactly one column is supplied then a single value is returned, otherwise a DataFrame of 1 value per column is produced.


------
##### cummax
```php
public function cummax(...$columns) 
```
Compute the cumulative maximum value for one or more columns.

If no column is specified then the the operation runs over all columns.

If exactly one column is supplied then a single value is returned, otherwise a DataFrame of 1 value per column is produced.


------
##### cummin
```php
public function cummin(...$columns) 
```
Compute the cumulative minimum value for one or more columns.

If no column is specified then the the operation runs over all columns.

If exactly one column is supplied then a single value is returned, otherwise a DataFrame of 1 value per column is produced.


------
##### cumproduct
```php
public function cumproduct(...$columns) 
```
Compute the cumulative product for one or more columns.

If no column is specified then the the operation runs over all columns.

If exactly one column is supplied then a single value is returned, otherwise a DataFrame of 1 value per column is produced.


------
##### median
```php
public function median(...$columns) 
```
Find the median value for one or more columns.

If no column is specified then the the operation runs over all columns.

If exactly one column is supplied then a single value is returned, otherwise a DataFrame of 1 value per column is produced.


------
##### product
```php
public function product(...$columns) 
```
Compute the product for one or more columns.

If no column is specified then the the operation runs over all columns.

If exactly one column is supplied then a single value is returned, otherwise a DataFrame of 1 value per column is produced.


------
##### variance
```php
public function variance(...$columns) 
```
Compute the variance for one or more columns.

If no column is specified then the the operation runs over all columns.

If exactly one column is supplied then a single value is returned, otherwise a DataFrame of 1 value per column is produced.


------
##### normalise
```php
public function normalise(...$columns) 
```
Normalise one or more columns to a range between 0 and 1.

If no column is specified then the the operation runs over all columns.

If exactly one column is supplied then a single array is returned, otherwise a DataFrame with the given columns is produced.


------
##### normalize
```php
public function normalize(...$columns) 
```
Alias of self::normalise().


------
##### quartile
```php
public function quartile($q, $column = null) 
```
Alias of self::quantile().


------
##### quantile
```php
public function quantile($q, $column = null) 
```
Compute the value for a given quantile for one or more columns.

If no column is specified then the the operation runs over all columns.

If exactly one column is supplied then a single value is returned, otherwise a DataFrame of 1 value per column is produced.


------
##### round
```php
public function round($precision, int $mode = PHP_ROUND_HALF_UP) 
```
Round all values in the DataFrame up or down to the given decimal point precesion.


------
##### correlation
```php
public function correlation(string $method, array $columns = null) 
```
Run a correlation over one or more columns to find similarities in values.

The resulting DataFrame is a matrix of values representing the closeness of the ajoining values.

- **$method** Correlation method to use. Accepted values are 'pearson' or 'spearman'.
- **$columns** Columns to use for the correlation. If no column is specified then the the operation runs over all columns.


------
##### corr
```php
public function corr(string $method, array $columns = null) 
```
Alias of correlation().


------
##### summary
```php
public function summary() 
```
No documentation available.


------
##### groupby
```php
public function groupby(string $column) 
```
Produce a set of seperate DataFrames whereby all rows of the current DataFrame are split by the given column.

The result is a GroupedDataFrame, containing all resulting DataFrames within.


------
##### drop_columns
```php
public function drop_columns($columns, $inplace = false) 
```
Remove the specified columns from the DataFrame.

If $inPlace is `TRUE` then this operation modifies the current DataFrame, otherwise a copy is returned.


------
##### drop_rows
```php
public function drop_rows($start, $end = null, $inplace = false) 
```
Remove the rows starting at $start and ending at $end from the DataFrame, where $start and $end represent the relevant row indexes.

If $inPlace is `TRUE` then this operation modifies the current DataFrame, otherwise a copy is returned.


------
##### duplicated
```php
public function duplicated(...$columns) 
```
Find all duplicate values for a given set of columns, or every column if none are supplied.

This method only compares corresponding values between rows of each column. That is, it the comparison is performed vertically, not horizontally.


------
##### drop_duplicates
```php
public function drop_duplicates($inplace = false, ...$columns) 
```
No documentation available.


------
##### pivot
```php
public function pivot(...$columns) 
```
Generate a copy of the DataFrame with the columns and row indexes rotated to become the other.

This has the effect of grouping common values under a singular index.

If a set of columns are provided then all other columns are stripped out of the result.

**Returns:**  A new DataFrame with the modified data.


------
##### depivot
```php
public function depivot(...$columns) 
```
The reverse operation of pivot(). Rotate the row indexes and columns back in the other direction.

Note that $columns in this method actually refer  to the current grouped indexes that you wish to revert back into actual columns.

If no columns are supplied then all indexes are used.

**Returns:**  A new DataFrame with the modified data.


------
##### transpose
```php
public function transpose(string $groupColumn, array $mergeMap) 
```
Perform a complex transformation on the DataFrame where by the column specified by $groupColumn becomes the index and all other columns are merged via the merge map.

The $mergeMap is an associative array where by each column name specified as a key becomes a column in the resulting DataFrame and each column name specified as a value in the array becomes the corresponding value of that column.

**Returns:**  A new DataFrame with the transposed data.


------
##### transform
```php
public function transform($callback, ...$columns) 
```
Transform the value of one or more columns using the provided callback. If no columns are specified then the operation applies to all.

Callback format: `myFunc($value, $columnName, $rowIndex) -> mixed`


------
##### add_row
```php
public function add_row(array $row = [], $index = '') 
```
Add a new row to the DataFrame. $row is an associative array where the keys should correspond to one or more of the column headers in the DataFrame.

$index is an optional keyed index to store the row against. If left empty then the next sequential number shall be used.

Do not use new or unknown keys not already present in the DataFrame.


------
##### add_column
```php
public function add_column(string $column, callable $callback) 
```
Add a new column to the DataFrame using the provided callback to supply the data. The callback will be called for every row currently in the DataFrame.

Callback format: `myFunc($row, $rowIndex)` - $row: associative array containing the value for each column.


------
##### apply_display_transformer
```php
public function apply_display_transformer($callback, ...$columns) 
```
Apply a transformation callback for one or more columns when outputing the DataFrame. If no columns are specified then the operation applies to all.

You might use this to format timestamps into dates or to unify the display of currency.

The callback should return the formatted value as it should be displayed.

This method does not modify the original value within the Dataframe.

Callback format: `myFunc($value) -> mixed`


------
##### plot
```php
public function plot(string $type, array $options = []) 
```
Produce a plot object (from the plotlib module) auto-configured to create an image-based graph of one or more columns.

- **$options** represent the chart configuation.
	- title: 		Filename of the chart. Defaults to the chart type and series being plotted.
	- columns: 	Array of the column names to produce charts for.
	- xcolumn: 	A column name to use as the x-axis.
	- one: 		If `TRUE` then all columns will be rendered onto one chart. When `FALSE` multiple charts are generated.
	- min:			The minimum Y-value to render.
	- max:			The maximum Y-value to render.
	- lines:		Array of infinite lines to be drawn onto the chart. Each item in the array is an associative array containing the following options:
		- direction:    Either VERTICAL or HORIZONTAL.
		- value:        the numerical position on the respective axis that the line will be rendered.
		- color:        A colour name (e.g. red, blue etc) for the line colour. Default is red.
		- width:        The stroke width of the line, default is 1.
	- labelangle:	  Angular rotation of the x-axis labels, default is 0.
	- bars:		A liniar array of values to represent an auxiliary/background bar chart dataset. This will plot on it's own Y axis.
	- barColor:	The colour of the bars dataset, default is 'lightgray'.
	- barWidth:	The width of each bar in the bars dataset, default is 7.
- **$type** represents the type of chart (e.g line, box, bar etc). Possible values:
	- line: 		line chart.
	- linefill: 	line chart with filled area.
	- bar:			bar chart.
	- barstacked:	bar chart with each series stacked atop for each data point.
	- scatter:		scatter chart.
	- box:			Similar to a stock plot but with a fifth median value.

**Returns:**  A BulkPlot object containing the plots to be rendered. See: plotlib for possibly more information.


------
##### stock
```php
public function stock(string $openP, string $closeP, string $lowP, string $highP, array $options = []) 
```
Produce a candle-stick chart, typically used for tracking stock prices.

You must specify exactly 4 columns.

$options can include a 'volume' key, specifying an associative array with the subkeys 'key', 'color' and 'width' for representing volume as a background bar chart.

All other standard option keys can be passed in.

**Returns:**  A BulkPlot object containing the plots to be rendered.


------
##### box
```php
public function box(...$columns) 
```
Create a box plot chart, which is a singular data point of box-like appearance that illustrates the place of the 25%, 50% and 75% quantiles as well as the outer whiskers.

**Returns:**  A BulkPlot object containing the plots to be rendered.


------
##### hist
```php
public function hist(array $options = []) 
```
Create a bar chart styled in the fashion of a histogram.

- **$options** is an array containing the following:
	- columns:      array of column names to use (1 or more)
	- bins:         number of bins to use for the histogram. Defaults to 10.
	- cumulative:   create a stacked histogram showing the accumulative scale along with the main. Defaults to `FALSE`.
	- title:        displayed title of the histogram.
	- low:          low range bins filter. Defaults to `NULL`.
	- high:         high range bins filter. Defaults to `NULL`.

**Returns:**  A BulkPlot object containing the plots to be rendered.


------
##### export
```php
public function export(string $filePath, array $columns = null, string $delimeter = ',', bool $includeIndex = true) 
```
Export the Dataframe to a delimetered text file (CSV).

- **$filePath:** The destination file.
- **$columns:** The columns to export, or all if null is supplied.
- **$delimeter:** The character that seperates each column.
- **$includeIndex:** When `TRUE`, adds the dataframe row index as the first column.


------
