# PHEXT Datakit

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.3-8892BF.svg)](https://php.net/)
[![License](https://sqonk.com/opensource/license.svg)](license.txt)![Build Status](https://travis-ci.org/sqonk/phext-datakit.svg?branch=master)

Datakit is a library that assists with data analysis and research. 

It also contains a small set of stand-alone functions and defined constants that import across the global namespace.


## About PHEXT

The PHEXT package is a set of libraries for PHP that aim to solve common problems with a syntax that helps to keep your code both concise and readable.

PHEXT aims to not only be useful on the web SAPI but to also provide a productivity boost to command line scripts, whether they be for automation, data analysis or general research.

## Install

Via Composer

``` bash
$ composer require sqonk/phext-datakit
```

Method Index
------------

* [Importer](#importer-methods)
* [SMA](#sma-methods)
* [EMA](#ema-methods)
* [DOMScraper](#domscraper-methods)
* [Vector](#vector-methods)
* [DataFrame](#dataframe-methods)
* [GroupedDataFrame](#groupeddataframe-methods)
* [PackedSequence](#packedsequence-methods)
* [PackedArray](#packedarray-methods)

Datakit Features
----------------

* [Importer](#importer) for working with CSV and delimitered files
	- [CSV Files](#importer---csv---files)
	- [CSV In Memory](#importer---csv---data)
	- [CSV Automated Processing](#importer---csv---data)
	- [Generic Delimitered import](#importer---generic)
* [SMA](#sma) Simple Moving Average calculator
* [EMA](#ema): Exponential Moving Average calculator
* [DOMScraper](#domscaper): A light weight and unsophisticated web scraper
* [PackedSequence and PackedArray](#packedsequence-and-packedarray)
  * [Adding and Removing](#packed-structures---adding-and-removing)
  * [Head, Tail and Slice](#packed-structures---head-tail-and-slice)
  * [Calculations](#packed-structures---calculations)
  * [Map and Filter](#packed-structures---map-and-filter)
  * [Sorting and Clip](#packed-structures---sorting-and-clip)
* [Vector](#vector): An object orientated wrapper for native PHP arrays, including functions for basic statistical calculations.
	- [Array Modification](#vector---array-modification)
	- [Data Manipulation](#vector---data-manipulation)
		* [Adding and setting elements](#vector---data-manipulation---adding-and-setting-elements)
		* [Removing elements](#vector---data-manipulation---removing-elements)
		* [Fill, Prefill, Pad](#vector---data-manipulation---fill-prefill-pad)
		* [Mapping](#vector---data-manipulation---mapping)
	- [Calculations](#vector---Calculations)
	- [Filtering and Sorting](#vector---filtering-and-sorting)
		* [Sorting](#vector---filtering---sorting)
		* [Filtering](#vector---filtering---filtering)
	- [Transforms](#vector---transforms) 	
		* [Chunking](#vector---transforms---chunk)
		* [Implode](#vector---transforms---implode)
		* [Group By](#vector---transforms---GroupBy)
		* [Transpose](#vector---transforms---transpose)
* [DataFrame](#dataframe): for performing statistical analysis with 2-dimensional arrays / matrices.
	- [DataFrame Modification](#dataframe---modification)
		* [Extracting subsections and random samples](#dataframe---modification---subsets)
		* [Re-indexing](#dataframe---modification---reindexing)
	- [Data Manipulation](#dataframe---data-manipulation)
		* [Adding columns and rows](#dataframe---data-manipulation---adding-rows-and-columns)
		* [Removing columns rows](#dataframe---data-manipulation---removing-rows-and-columns)
		* [Transforming data](#dataframe---data-manipulation---transforming)
		* [Duplicate detection and removal](#dataframe---data-manipulation---duplicates)
		* [Clipping and pruning](#dataframe-data-manipulation---clipping)
	- [Iteration](#dataframe---iteration)
	- [Summaries and readouts](#datafame---printing)
		* [Printing data to the console and to string](#dataframe---printing---report)
		* [Summary](#dataframe---printing---summary)
		* [Shape](#dataframe---printing---shape)
		* [Quantiles](#dataframe---printing---quantiles)
		* [Presentation Transformers](#dataframe---printing---transformers)
		* [Out of Bounds detection](#dataframe---printing---oob)
		* [Data gap detection](#dataframe---printing---gaps)
	- [Calculations](#dataframe---calculations)
	- [Filtering and Sorting](#dataframe---filtering)
		* [Sorting](#dataframe---filtering---sorting)
		* [Filtering](#dataframe---filtering---filtering)
	- [Transforms](#dataframe---transforms)
		* [Pivot and Depivot](#dataframe---transforms---pivot-and-depivot)
		* [Group-by and recombination](#dataframe---transforms---groupby)
		* [Transpose](#dataframe---transforms---transpose)
		* [Flatten](#dataframe---transforms---flatten)
	- [Plotting Data on Charts](#dataframe---charting)
		* [Histogram](#dataframe---charting---hist)
		* [Quantile Box Plot](#dataframe---charting---box)
		* [General chart generation](#dataframe---charting---general)
	- [Export to CSV](#dataframe---export)
* [Defined Constants](#defined-constants)


Usage
-----

### Importer

The Importer class allows easy importing of both small and large CSV files.

#### Importer - CSV - Files

Import a CSV direct from file and print out some of the columns.

``` php
use sqonk\phext\datakit\Importer as import;

$iris_columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

import::csv_file(function($row) {
	println($row['class'], $row['sepal-length']);
}, 
'docs/iris.data', false, $iris_columns);
```

Using a foreach loop with a generator, instead of a callback.

```php
use sqonk\phext\datakit\Importer as import;

$iris_columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

foreach (import::yield_csv('docs/iris.data', false, $iris_columns) as $row)
    println($row['class'], $row['sepal-length']);
```

Import a CSV from file directly into a DataFrame.

```php
use sqonk\phext\datakit\Importer as import;

$iris_columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$df = import::csv_dataframe('docs/iris.data', $iris_columns);
```



#### Importer - CSV - Data

Import a CSV already loaded into memory and print out some of the columns.

``` php
use sqonk\phext\datakit\Importer as import;

$iris_columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$data = file_get_contents('docs/iris.data');

import::csv_data(function($row) {
	println($row['class'], $row['sepal-length']);
}, 
$data, false, $iris_columns);
```

#### Importer - CSV - Automated processing

For CSV files that can be loaded into memory in entirety, if you have no special processing to do of each row then you may omit the callback and simply receive the data at the end of the call.

``` php
use sqonk\phext\datakit\Importer as import;

$iris_columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$rows = import::csv_file(null, 'docs/iris.data', false, $iris_columns);
```

#### Importer - Generic

Import a tab delimitered dataset already loaded into memory and print out the columns.

``` php
use sqonk\phext\datakit\Importer as import;

$data = <<<EOT
Name\tAge\tOccupation
Dave\t43\tDriver
Alex\t26\tMarketing
Grace\t54\tDesign	
EOT;

import::delimitered_data(function($row) {
	println($row['Name'], $row['Age'], $row['Occupation']);
}, 
$data, "\t", "\n", true);
```
### SMA

The SMA class is used to compute Simple Moving Averages. It is used by alternating between adding new values to the array and calculating the current average.

``` php
use sqonk\phext\datakit\SMA;

// Create a new SMA with a rolling set of 5 possible values.
$sma = new SMA(5); 

foreach (range(1, 20) as $i)
{
	$sma->add($i); // add the latest value to the moving average.
	
	// generate the result and print it.
	$avg = $sma->result(3);
	println("Position $i, Average: $avg");
}
```

### EMA

The EMA class is used to compute Exponential Moving Averages. It is used by alternating between adding new values to the array and calculating the current average.

``` php
use sqonk\phext\datakit\EMA;

// Create a new EMA with a rolling set of 5 possible values.
$sma = new EMA(5); 

foreach (range(1, 20) as $i)
{
	$sma->add($i); // add the latest value to the moving average.
	
	// generate the result and print it.
	$avg = $sma->result(3);
	println("Position $i, Average: $avg");
}
```

### DOMScaper

The DOMScraper is a barebones web scraper that works by quickly traversing a series of nested elements in a DOMDocument and delivering the final set of elements to a callback for processing.

``` php
use sqonk\phext\datakit\DOMScraper;

// Load the example HTML file into memory and pass it to the scraper.
$scraper = new DOMScraper(file_get_contents('docs/people.html'));

$scraper->traverse([
	['type' => 'id', 'name' => 'pageData'],
	['type' => 'tag', 'name' => 'table', 'item' => 1],
	['type' => 'tag', 'name' => 'tbody'],
	['type' => 'tag', 'name' => 'tr']
], 
function($tr) {
	$tds = $tr->getElementsByTagName('td');
	
	$firstName = $tds[0]->textContent;
	$lastName = $tds[1]->textContent;
	$role = $tds[2]->textContent;
	$hours = $tds[3]->textContent;
	$days = $tds[4]->textContent;
	
	println("Name: $firstName $lastName", "Role: $role", "Works: $days ($hours)");
});
```



### PackedSequence and PackedArray

Both PackedSequence and PackedArray are array structures designed for working in tight memory situations. A full description is available further down in the method reference.

- Use a [PackSequence](#packedsequence-methods) for working with a uniform set of elements of the same type and byte size (e.g. all Ints or all floats).
- Use a [PackedArray](#packedarray-methods) when your dataset has elements that vary in size and/or type.

Both classes have almost identical methods and the examples below can easily be translated between the the two.

#### Packed Structures - Adding and Removing

PackedSequence modification. Note that both PackedArray and PackedSequence adhere to the array

interfaces and can be accessed and modified in the same fashion as a native array.

```php
use sqonk\phext\datakit\PackedSequence;

$ps = new PackedSequence('i', [1,2,3,4,5,6,7,8,9]);
$ps->print();
/*
[0] 1
[1] 2
[2] 3
[3] 4
[4] 5
[5] 6
[6] 7
[7] 8
[8] 9
*/

$ps->delete(1)->print("\ndelete");
/*
delete
[0] 1
[1] 3
[2] 4
[3] 5
[4] 6
[5] 7
[6] 8
[7] 9
*/

$ps->insert(4, 22)->insert(5, 33)->print("\ninsert");
/*
insert
[0] 1
[1] 3
[2] 4
[3] 5
[4] 22
[5] 33
[6] 6
[7] 7
[8] 8
[9] 9
*/

$ps->pop()->shift()->set(0, 30)->print("\npop shift set");
/*
pop shift set
[0] 30
[1] 4
[2] 5
[3] 22
[4] 33
[5] 6
[6] 7
[7] 8
*/

$ps->clear()->add(1,2,3)->print("\nreset");
/*
reset
[0] 1
[1] 2
[2] 3
*/
```

The same methods exist in PackedArray. The only difference is in the object creation, which can be done as follows:

```php
use sqonk\phext\datakit\PackedArray;

$pa = new PackedArray([1,2,3,4,5,6,7,8,9]);
```



#### Packed Structures - Head, Tail and Slice

```php
use sqonk\phext\datakit\PackedSequence;

$ps = new PackedSequence('i', [1,2,3,4,5,6,7,8,9]);

$ps->head(3)->print("\nhead");
/*
head
[0] 1
[1] 2
[2] 3
*/

$ps->tail(3)->print("\ntail");
/*
tail
[0] 7
[1] 8
[2] 9
*/

$ps->slice(2, 1)->print("\nslice");
/*
slice
[0] 3
*/
```



#### Packed Structures - Calculations

```php
use sqonk\phext\datakit\PackedSequence;

$ps = new PackedSequence('i', [1,2,3,4]);
println('sum:', $ps->sum(), 'avg:', $ps->avg(), 'product:', $ps->product(), 'min:', $ps->min(), 'max:', $ps->max(), 'variance:', $ps->variance());
/*
sum: 10 avg: 2.5 product: 24 min: 1 max: 4 variance: 1.25
*/
```



#### Packed Structures - Map and Filter

```php
use sqonk\phext\datakit\PackedSequence;

$pa = new PackedSequence('i', [1,2,3,4]);

// Add 5 to the value of all elements.
$pa->map(fn($v) => $v+5)->print();
/*
[0] 6
[1] 7
[2] 8
[3] 9
*/

// Reset and filter out even numbers.
println("\nfilter");
$pa->clear()->add(1,2,3,4)->filter(fn($v, $i) => $v % 2)->print();
/*
filter
[0] 1
[1] 3
*/

// Check for the occurance of a value.
println('At least one lement is 3:', $pa->any(3));
println('All elements are 3:', $pa->all(3));
/*
At least one lement is 3: 1
All elements are 3: 
*/

// Check value of first element and last element
println('starts with 3', $pa->starts_with(1), 'ends with 4:', $pa->ends_with(4));
/*
starts with 3 1 ends with 4: 1
*/
```



#### Packed Structures - Sorting and Clip

```php
use sqonk\phext\datakit\PackedArray;

$ps = new PackedArray(['john', 'sarah', 'derek', 'cameron']);
$ps->print();
/*
[0] john
[1] sarah
[2] derek
[3] cameron
*/

// Sort in ascending order.
$ps->sort()->print("\nsorted");
/*
sorted
[0] cameron
[1] derek
[2] john
[3] sarah
*/

// Sort in descending order.
$ps->sort(DESCENDING)->print("\nsorted reversed");
/*
sorted reversed
[0] sarah
[1] john
[2] derek
[3] cameron
*/

// Clip all values to be within a minimum and maximum value (inclusive).
$ps->clip(4.5, 5.0)->print("\nclip");
/*
clip
[0] 4.5
[1] 4.5
[2] 5
[3] 4.5
[4] 4.5
[5] 4.9
[6] 5
[7] 5
[8] 5
*/
```



### Vector

Vector is an object orientated wrapper for native PHP arrays. It exposes most of the native built-in methods as well as adding additional functions useful for both basic statistical calculations and bulk operations.


#### Vector - Array Modification

``` php
// NOTE: Converting a vector to a string exposes the underlying array in the var dump for readability.

$data = vector('Apple', 'Orange', 'Banana', 'Rasberry', 'Kiwi', 'Melon', 'Manderin', 'Pear');

// Return a vector containing the first 3 items.
println($data->head(3));
/*
array (
  0 => 'Apple',
  1 => 'Orange',
  2 => 'Banana',
)
*/

// Return a vector containing the first 3 items.
println($data->tail(3));
/*
array (
  0 => 'Melon',
  1 => 'Manderin',
  2 => 'Pear',
)
*/

// Return a subsection of the array, starting at the third item and containing 2 items in total.
println($data->slice(2, 2));
/*
array (
  0 => 'Banana',
  1 => 'Rasberry',
)
*/

// Obtain a subsection containing a random sample of the values within.
// The sample will contain a minimum of 2 elements.
println($data->sample(2));
/*
	Printed result will be different everytime you run it.
*/

// swap the indexes and values around.
println($data->flip());
/*
array (
  'Apple' => 0,
  'Orange' => 1,
  'Banana' => 2,
  'Rasberry' => 3,
  'Kiwi' => 4,
  'Melon' => 5,
  'Manderin' => 6,
  'Pear' => 7,
)
*/

// NOTE: shift() and pop() method below modify the original vector.

// Pop 2 elements off of the end.
println($data->pop(2));
/*
array (
  0 => 'Apple',
  1 => 'Orange',
  2 => 'Banana',
  3 => 'Rasberry',
  4 => 'Kiwi',
  5 => 'Melon',
)
*/

// Shift 2 element off of the start.
println($data->shift(2));
/*
array (
  0 => 'Banana',
  1 => 'Rasberry',
  2 => 'Kiwi',
  3 => 'Melon',
)
*/
```

#### Vector - Data Manipulation

##### Vector - Data Manipulation - Adding and setting elements

``` php
$data = vector('Apple', 'Orange', 'Banana', 'Rasberry', 'Kiwi', 'Melon', 'Manderin', 'Pear');


// Adding items to the vector.
$data->add('Apple');
$data[] = 'Another Organge';

// Add an item to the start of the vector.
$data->prepend('Grape');

// Replace an existing item or set an item with a custom index.
$data->set('Custom Key', 'Green Grape');

// Print out what we have.
println($data);
/*
array (
  0 => 'Grape',
  1 => 'Apple',
  2 => 'Orange',
  3 => 'Banana',
  4 => 'Rasberry',
  5 => 'Kiwi',
  6 => 'Melon',
  7 => 'Manderin',
  8 => 'Pear',
  9 => 'Apple',
  10 => 'Another Organge',
  'Custom Key' => 'Green Grape',
)
*/
```

##### Vector - Data Manipulation - Removing elements

``` php
$data = vector('Apple', 'Orange', 'Banana', 'Rasberry', 'Kiwi', 'Melon', 'Manderin', 'Pear', '',  '');

// Remove elements from the vector.
$data->remove(3, 4, 5);
println($data);
/*
array (
  0 => 'Apple',
  1 => 'Orange',
  2 => 'Banana',
  3 => 'Manderin',
  4 => 'Pear',
  5 => '',
  6 => '',
)
*/

// Prune empty values.
$data->prune();
println($data);
/*
array (
  0 => 'Apple',
  1 => 'Orange',
  2 => 'Banana',
  3 => 'Manderin',
  4 => 'Pear',
)
*/

// Clear out the vector.
$data->clear();
println($data);
/*
array (
)
*/
```

##### Vector - Data Manipulation - Fill Prefill Pad

``` php
$data = vector();


// fill and prefill.
$data = $data->fill(5, fn($index) => $index+1);
println($data);
/*
array (
  0 => 1,
  1 => 2,
  2 => 3,
  3 => 4,
  4 => 5,
)
*/

$data = $data->prefill(3, fn($index) => 5);
println($data);
/*
array (
  0 => 5,
  1 => 5,
  2 => 5,
  3 => 1,
  4 => 2,
  5 => 3,
  6 => 4,
  7 => 5,
)
*/

$data->pad(10, 'unused slot');
println($data);
/*
array (
  0 => 5,
  1 => 5,
  2 => 5,
  3 => 1,
  4 => 2,
  5 => 3,
  6 => 4,
  7 => 5,
  8 => 'unused slot',
  9 => 'unused slot',
)
*/
```

##### Vector - Data Manipulation - Mapping

``` php
$data = vector(1, 2, 3, 4);
$data = $data->map(fn($value, $index) => $value * 10);
println($data);
/*
array (
  0 => 10,
  1 => 20,
  2 => 30,
  3 => 40,
)
*/
```

#### Vector - Calculations

``` php
$data = vector(1, 2, 3, 4, 2, 3, 5, 6, 7, 8);

// Sum
println($data->sum());
// 41

// Max value
println($data->max());
// 8

// Min value
println($data->min());
// 1


// Average
println($data->avg());
// 4.1

// Median
println($data->median());
// 2.5

// Product
println($data->product());
// 241920

// Cumulative Sum
println($data->cumsum());
/*
array (
  0 => 1,
  1 => 3,
  2 => 6,
  3 => 10,
  4 => 12,
  5 => 15,
  6 => 20,
  7 => 26,
  8 => 33,
  9 => 41,
)
*/

// Cumulative Max
println($data->cummax());
/*
array (
  0 => 1,
  1 => 2,
  2 => 3,
  3 => 4,
  4 => 4,
  5 => 4,
  6 => 5,
  7 => 6,
  8 => 7,
  9 => 8,
)
*/

// Cumulative Min
println($data->cummin());
/*
array (
  0 => 1,
  1 => 1,
  2 => 1,
  3 => 1,
  4 => 1,
  5 => 1,
  6 => 1,
  7 => 1,
  8 => 1,
  9 => 1,
)
*/

// Cumulative Product
println($data->cumproduct());
/*
array (
  0 => 1,
  1 => 2,
  2 => 6,
  3 => 24,
  4 => 48,
  5 => 144,
  6 => 720,
  7 => 4320,
  8 => 30240,
  9 => 241920,
)
*/

// Variance
println($data->variance());
// 4.89

// Frequency (amount of times each unique value occurs within the vector).
println($data->frequency());
/*
array (
  1 => 1,
  2 => 2,
  3 => 2,
  4 => 1,
  5 => 1,
  6 => 1,
  7 => 1,
  8 => 1,
)
*/
```

#### Vector - Filtering and Sorting

##### Vector - Filtering and Sorting - Filtering

``` php
$data = vector(1, 2, 3, 4, 2, 3, 5, 6, 7, 8);

// Do any of the value match.
println('contains 4:', $data->any(4));
// contains 4: 1

// Do all values match.
println('contains 4:', $data->all(4));
// contains 4: 

// Do the first element match
println('starts with 1:', $data->starts_with(1));
// starts with 1: 1

// Do the last element match
println('starts with 8:', $data->ends_with(8));
// starts with 8: 1

// Filter out all even numbers
println($data->filter(fn($v) => $v % 2));
/*
array (
  0 => 1,
  2 => 3,
  5 => 3,
  6 => 5,
  8 => 7,
)
*/

// All unique values.
println($data->unique());
/*
array (
  0 => 1,
  1 => 2,
  2 => 3,
  3 => 4,
  6 => 5,
  7 => 6,
  8 => 7,
  9 => 8,
)
*/

// Difference between other arrays or vectors
println($data->diff([9, 1, 3], vector(2, 5)));
/*
array (
  3 => 4,
  7 => 6,
  8 => 7,
  9 => 8,
)
*/

// Common values between other arrays or vectors
println($data->intersect([9, 1, 3]));
/*
array (
  0 => 1,
  2 => 3,
  5 => 3,
)
*/

// Return the first element to occur within a string.
$states = vector('Melbourne', 'Sydney', 'Tasmania', 'Brisbane', 'Darwin', 'Perth', 'Adelaide');
println($states->occurs_in('I went for a trip to Sydney'));
// Sydney

// Select a random element from the vector
println($states->choose());
```

##### Vector - Filtering and Sorting - Sorting

``` php
$data = vector(1, 2, 3, 4, 2, 3, 5, 6, 7, 8);


// Sort the vector in ascending order
println($data->sort(ASCENDING));
/*
array (
  0 => 1,
  1 => 2,
  2 => 2,
  3 => 3,
  4 => 3,
  5 => 4,
  6 => 5,
  7 => 6,
  8 => 7,
  9 => 8,
)
*/

// Sort the vector in ascending order
println($data->sort(DESCENDING));
/*
array (
  0 => 8,
  1 => 7,
  2 => 6,
  3 => 5,
  4 => 4,
  5 => 3,
  6 => 3,
  7 => 2,
  8 => 2,
  9 => 1,
)
*/
```

##### Vector - Transforms - Implode

Implode the vector into a string using a delimiter. This assumes all elements within the vector can be stringified.

``` php
$data = vector('Mon', 'Tue', 'Wed', 'Thu', 'Fri');
println($data->implode(', '));
// Mon, Tue, Wed, Thu, Fri
```

##### Vector - Transforms - Chunk

Split the vector into batches.

``` php
$data = vector('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');

$batches = $data->chunk(2);
println($batches->count(), 'batches');
// 4 batches

foreach ($batches as $batch)
	println($batch->implode(', '));

/*
Sun, Mon
Tue, Wed
Thu, Fri
Sat
*/
```


##### Vector - Transforms - Group By

This example demonstrates the use of groupby(), which is a subsumarisation 
routine for grouping a flat list of rows into related sets.

The steps are:
- acquire the flat list of records.
- sort the array by the same keys that we wish to group by.
- run the group_by to transform the array into a heirarchy.

Transform a flat set of weekday/month/day information into month -> weekdays -> days.

``` php
// First build our sample dataset, a range of date information 
// starting from today and going back by a little more
// than two months.
$days = vector()->fill(70, function($i) {
	$time = strtotime("-$i day");
	return ['weekday' => date('D', $time), 'month' => date('M', $time), 'day' => date('d', $time)];
});

// Now split by the desired keys
$sets = $days->groupby(['month', 'weekday']);
foreach ($sets as $month => $weekdays)
{
	println('===========', $month, '');
	foreach ($weekdays as $wd => $calendarDays)
	{
		// $calendarDays is an subset of the relevant original rows/records in $days.
		println("$wd:", implode(',', array_column($calendarDays, 'day')));
	}
}

/*
=========== Apr 
Fri: 10,03
Mon: 06
Sat: 04
Sun: 05
Thu: 02,09
Tue: 07
Wed: 08,01
=========== Feb 
Fri: 14,28,21,07
Mon: 10,17,24,03
Sat: 15,08,22,29,01
Sun: 09,02,23,16
Thu: 20,13,27,06
Tue: 18,25,11,04
Wed: 05,26,19,12
=========== Mar 
Fri: 27,13,06,20
Mon: 02,09,23,16,30
Sat: 21,14,28,07
Sun: 29,22,01,08,15
Thu: 26,05,19,12
Tue: 24,17,03,10,31
Wed: 25,04,11,18
*/
```

##### Vector - Transforms - Transpose

This example demonstrates the use of transpose(), which can be used for shifting vertically aligned information in a 2-dimensional matrix into horizontal set.

This hypothetical dataset includes two movie characters and their amount of appearances in movies over the decades.

``` php
use sqonk\phext\core\strings;

$data = vector(
    ['character' => 'Actor A', 'decade' => 1970, 'appearances' => 1],
    ['character' => 'Actor A', 'decade' => 1980, 'appearances' => 2],
    ['character' => 'Actor A', 'decade' => 1990, 'appearances' => 2],
    ['character' => 'Actor A', 'decade' => 2000, 'appearances' => 1],
    ['character' => 'Actor A', 'decade' => 2010, 'appearances' => 1],
    
    ['character' => 'Actor B', 'decade' => 1980, 'appearances' => 1],
    ['character' => 'Actor B', 'decade' => 1990, 'appearances' => 1],
    ['character' => 'Actor B', 'decade' => 2000, 'appearances' => 1],
);
println(strings::columnize($data->array(), ['decade', 'character', 'appearances']));
/*
     	decade	character	appearances
_____	______	_________	___________
0    	  1970	  Actor A	          1
1    	  1980	  Actor A	          2
2    	  1990	  Actor A	          2
3    	  2000	  Actor A	          1
4    	  2010	  Actor A	          1
5    	  1980	  Actor B	          1
6    	  1990	  Actor B	          1
7    	  2000	  Actor B	          1
*/

// Transform the matrix using transpose() so that each character becomes a column
// with their resulting appearances listed alongside the decade.
println("\n\n", "------ Shifted into one column per character with resulting appearance count ------");
$transformed = $data->transpose('decade', ['character' => 'appearances']);
println(strings::columnize($transformed->array(), ['decade', 'Actor A', 'Actor B']));

/*
     	decade	Actor A	Actor B
_____	______	_______	_______
0    	  1970	      1	       
1    	  1980	      2	      1
2    	  1990	      2	      1
3    	  2000	      1	      1
4    	  2010	      1	       
*/
```

### DataFrame

The DataFrame is a class inspired by, and loosely based off of, a class by the same name from the Pandas library in Python. It specialises in working with 2 dimensional arrays (rows and columns) that may originate from data sources such as CSV files or data fetched from a relational database.

Various basic statistical and mathematical functions are provided as well numerous methods for transforming and manipulating the underlying data and presentation thereof.

#### Dataframe Modification 

##### Dataframe - Modification - Subsets

``` php
use sqonk\phext\datakit\Importer as import;

$columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$dataset = import::csv_dataframe('docs/iris.data', $columns);

// First 5 rows
println($dataset->head(5));
/*
     	sepal-length	sepal-width	petal-length	petal-width	      class
_____	____________	___________	____________	___________	___________
0    	         5.1	        3.5	         1.4	        0.2	Iris-setosa
1    	         4.9	        3.0	         1.4	        0.2	Iris-setosa
2    	         4.7	        3.2	         1.3	        0.2	Iris-setosa
3    	         4.6	        3.1	         1.5	        0.2	Iris-setosa
4    	         5.0	        3.6	         1.4	        0.2	Iris-setosa
*/


// Last 5 rows
$dataset->tail(5)->print();
/*
     	sepal-length	sepal-width	petal-length	petal-width	         class
_____	____________	___________	____________	___________	______________
145  	         6.7	        3.0	         5.2	        2.3	Iris-virginica
146  	         6.3	        2.5	         5.0	        1.9	Iris-virginica
147  	         6.5	        3.0	         5.2	        2.0	Iris-virginica
148  	         6.2	        3.4	         5.4	        2.3	Iris-virginica
149  	         5.9	        3.0	         5.1	        1.8	Iris-virginica
*/

// Random sample of between 5 to 10 rows
$dataset->sample(5, 10)->print();
// will print a different set everytime the script is run.

/*
     	sepal-length	sepal-width	petal-length	petal-width	      class
_____	____________	___________	____________	___________	___________
3    	         4.6	        3.1	         1.5	        0.2	Iris-setosa
4    	         5.0	        3.6	         1.4	        0.2	Iris-setosa
5    	         5.4	        3.9	         1.7	        0.4	Iris-setosa
6    	         4.6	        3.4	         1.4	        0.3	Iris-setosa
7    	         5.0	        3.4	         1.5	        0.2	Iris-setosa
8    	         4.4	        2.9	         1.4	        0.2	Iris-setosa
*/


// Rows 10 - 19
$dataset->slice(10, 10)->print();
/*
     	sepal-length	sepal-width	petal-length	petal-width	      class
_____	____________	___________	____________	___________	___________
10   	         5.4	        3.7	         1.5	        0.2	Iris-setosa
11   	         4.8	        3.4	         1.6	        0.2	Iris-setosa
12   	         4.8	        3.0	         1.4	        0.1	Iris-setosa
13   	         4.3	        3.0	         1.1	        0.1	Iris-setosa
14   	         5.8	        4.0	         1.2	        0.2	Iris-setosa
15   	         5.7	        4.4	         1.5	        0.4	Iris-setosa
16   	         5.4	        3.9	         1.3	        0.4	Iris-setosa
17   	         5.1	        3.5	         1.4	        0.3	Iris-setosa
18   	         5.7	        3.8	         1.7	        0.3	Iris-setosa
19   	         5.1	        3.8	         1.5	        0.3	Iris-setosa
*/
```

##### Dataframe - Modification - Reindexing

``` php
use sqonk\phext\datakit\Importer as import;

$columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$dataset = import::csv_dataframe('docs/iris.data', $columns);


// reset the indexes on a subset of the original frame.
$subset = $dataset->slice(10, 10);
$subset->reindex_rows(range(0, $subset->count()-1), true);
$subset->print();
/*
     	sepal-length	sepal-width	petal-length	petal-width	      class
_____	____________	___________	____________	___________	___________
0    	         5.4	        3.7	         1.5	        0.2	Iris-setosa
1    	         4.8	        3.4	         1.6	        0.2	Iris-setosa
2    	         4.8	        3.0	         1.4	        0.1	Iris-setosa
3    	         4.3	        3.0	         1.1	        0.1	Iris-setosa
4    	         5.8	        4.0	         1.2	        0.2	Iris-setosa
5    	         5.7	        4.4	         1.5	        0.4	Iris-setosa
6    	         5.4	        3.9	         1.3	        0.4	Iris-setosa
7    	         5.1	        3.5	         1.4	        0.3	Iris-setosa
8    	         5.7	        3.8	         1.7	        0.3	Iris-setosa
9    	         5.1	        3.8	         1.5	        0.3	Iris-setosa
*/

// Re-index frame using the 'name' column.
$dataset = dataframe([
    ['name' => 'Falcon', 'Animal' => 'bird', 'Age' => 8, 'size' => 'big'],
    ['name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
    ['name' => 'Goat', 'Animal' => 'mammal', 'Age' => 12, 'size' => 'small'],
    ['name' => 'Possum', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big']
]);
$dataset->print();
/*
     	  name	Animal	Age	 size
_____	______	______	___	_____
0    	Falcon	  bird	  8	  big
1    	Pigeon	  bird	  4	small
2    	  Goat	mammal	 12	small
3    	Possum	mammal	  2	  big
*/

$dataset->reindex_rows_with_column('name')->print();
/*
      	Animal	Age	 size
______	______	___	_____
Falcon	  bird	  8	  big
Pigeon	  bird	  4	small
Goat  	mammal	 12	small
Possum	mammal	  2	  big
*/
```

#### Dataframe - Data Manipulation

##### Dataframe - Data Manipulation - Adding Rows and Columns

``` php
$dataset = dataframe([
    ['name' => 'Falcon', 'Animal' => 'bird', 'Age' => 8, 'size' => 'big'],
    ['name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
    ['name' => 'Goat', 'Animal' => 'mammal', 'Age' => 12, 'size' => 'small'],
    ['name' => 'Possum', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big']
]);

// Add a new row.
$dataset->add_row(['name' => 'Snail', 'Animal' => 'insect', 'Age' => 0.3, 'size' => 'small']);

// Add a new column to all rows.
$dataset->add_column('Mode', fn($row) => $row['Animal'] == 'bird' ? 'Flight' : 'Walk');

$dataset->print();

/*
     	  name	Animal	Age	 size	  Mode
_____	______	______	___	_____	______
0    	Falcon	  bird	  8	  big	Flight
1    	Pigeon	  bird	  4	small	Flight
2    	  Goat	mammal	 12	small	  Walk
3    	Possum	mammal	  2	  big	  Walk
4    	 Snail	insect	0.3	small	  Walk
*/
```

##### Dataframe - Data Manipulation - Removing Rows and Columns

``` php
$dataset = dataframe([
    ['name' => 'Falcon', 'Animal' => 'bird', 'Age' => 8, 'size' => 'big'],
    ['name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
    ['name' => 'Goat', 'Animal' => 'mammal', 'Age' => 12, 'size' => 'small'],
    ['name' => 'Possum', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big']
]);
$dataset->print();
/*
     	  name	Animal	Age	 size
_____	______	______	___	_____
0    	Falcon	  bird	  8	  big
1    	Pigeon	  bird	  4	small
2    	  Goat	mammal	 12	small
3    	Possum	mammal	  2	  big
*/

// Remove a row.
$dataset->drop_rows(1, null, true);

// Remove a column from all rows.
$dataset->drop_columns('Age', true);

$dataset->print();

/*
     	  name	Animal	 size
_____	______	______	_____
0    	Falcon	  bird	  big
2    	  Goat	mammal	small
3    	Possum	mammal	  big
*/
```

##### Dataframe - Data Manipulation - Transforming

The transform() method allows for the modification of the values within one or more columns.

``` php
$dataset = dataframe([
    ['recorded' => '2019-08-20', 'name' => 'Falcon', 'Animal' => 'bird', 'Age' => 8, 'size' => 'big'],
    ['recorded' => '2020-01-08', 'name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
    ['recorded' => '2019-06-27', 'name' => 'Goat', 'Animal' => 'mammal', 'Age' => 12, 'size' => 'small'],
    ['recorded' => '2018-05-01', 'name' => 'Possum', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big']
]);
	

// Convert the date stamp into a unix time.

$dataset->transform(fn($v) => strtotime($v), 'recorded');

$dataset->print('name', 'recorded');

/*
Output will vary depending on your timezone.

     	  name	  recorded
_____	______	__________
0    	Falcon	1566223200
1    	Pigeon	1578402000
2    	  Goat	1561557600
3    	Possum	1525096800
*/
```

##### Dataframe - Data Manipulation - Duplicates

Detection and removal of duplicate rows. Classification can be performed on specific columns or everything.

``` php
$dataset = dataframe([
    ['recorded' => '2019-08-20', 'name' => 'Falcon', 'Animal' => 'bird', 'Age' => 8, 'size' => 'big'],
    ['recorded' => '2020-01-08', 'name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
    ['recorded' => '2019-06-27', 'name' => 'Goat', 'Animal' => 'mammal', 'Age' => 12, 'size' => 'small'],
    ['recorded' => '2018-05-01', 'name' => 'Possum', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big'],
	['recorded' => '2020-01-08', 'name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
]);
	

println('indexes with duplicate content:', $dataset->duplicated());
/*
indexes with duplicate content: array (
  0 => 
  array (
    0 => 1,
    1 => 4,
  ),
)
*/

$dataset->drop_duplicates()->print();
/*

     	  recorded	  name	Animal	Age	 size
_____	__________	______	______	___	_____
0    	2019-08-20	Falcon	  bird	  8	  big
1    	2020-01-08	Pigeon	  bird	  4	small
2    	2019-06-27	  Goat	mammal	 12	small
3    	2018-05-01	Possum	mammal	  2	  big
*/
```

##### Dataframe - Data Manipulation - Clipping

Clipping will auto adjust values of a given column (or all) to a minimum or maximum value so that all values in the frame fall within range.

``` php
use sqonk\phext\datakit\Importer as import;

$columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$dataset = import::csv_dataframe('docs/iris.data', $columns)->head(10);

$dataset->print();
/*
     	sepal-length	sepal-width	petal-length	petal-width	      class
_____	____________	___________	____________	___________	___________
0    	         5.1	        3.5	         1.4	        0.2	Iris-setosa
1    	         4.9	        3.0	         1.4	        0.2	Iris-setosa
2    	         4.7	        3.2	         1.3	        0.2	Iris-setosa
3    	         4.6	        3.1	         1.5	        0.2	Iris-setosa
4    	         5.0	        3.6	         1.4	        0.2	Iris-setosa
5    	         5.4	        3.9	         1.7	        0.4	Iris-setosa
6    	         4.6	        3.4	         1.4	        0.3	Iris-setosa
7    	         5.0	        3.4	         1.5	        0.2	Iris-setosa
8    	         4.4	        2.9	         1.4	        0.2	Iris-setosa
9    	         4.9	        3.1	         1.5	        0.1	Iris-setosa
*/

// Clip the values of sepal-length so that no values 
// are below 4.5 or above 4.8

$dataset->clip(4.5, 4.8, 'sepal-length')->print();
/*
     	sepal-length	sepal-width	petal-length	petal-width	      class
_____	____________	___________	____________	___________	___________
0    	         4.8	        3.5	         1.4	        0.2	Iris-setosa
1    	         4.8	        3.0	         1.4	        0.2	Iris-setosa
2    	         4.7	        3.2	         1.3	        0.2	Iris-setosa
3    	         4.6	        3.1	         1.5	        0.2	Iris-setosa
4    	         4.8	        3.6	         1.4	        0.2	Iris-setosa
5    	         4.8	        3.9	         1.7	        0.4	Iris-setosa
6    	         4.6	        3.4	         1.4	        0.3	Iris-setosa
7    	         4.8	        3.4	         1.5	        0.2	Iris-setosa
8    	         4.5	        2.9	         1.4	        0.2	Iris-setosa
9    	         4.8	        3.1	         1.5	        0.1	Iris-setosa
*/
```

#### Dataframe - Iteration

``` php
use sqonk\phext\datakit\Importer as import;

$columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$dataset = import::csv_dataframe('docs/iris.data', $columns);


// Get the first row.
$row = $dataset[FIRST_ROW];
println($row);
/*
array (
  'sepal-length' => '5.1',
  'sepal-width' => '3.5',
  'petal-length' => '1.4',
  'petal-width' => '0.2',
  'class' => 'Iris-setosa',
)
*/

// Get the last row.
$row = $dataset[LAST_ROW];
println($row);
/*
array (
  'sepal-length' => '5.9',
  'sepal-width' => '3.0',
  'petal-length' => '5.1',
  'petal-width' => '1.8',
  'class' => 'Iris-virginica',
)
*/

// Loop through the frame.
foreach ($dataset as $row) {
	// custom code here.
}
```

#### Dataframe - Printing

##### Dataframe - Printing - Report

The DataFrame adheres to the stringable protocol and can be output via:

- standard echo/print call
- phext's println() method
- calling print() directly on the object

``` php
use sqonk\phext\datakit\Importer as import;

$columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$dataset = import::csv_dataframe('docs/iris.data', $columns)->head(10);

// Print the whole frame.
$dataset->print();
/*
     	sepal-length	sepal-width	petal-length	petal-width	      class
_____	____________	___________	____________	___________	___________
0    	         5.1	        3.5	         1.4	        0.2	Iris-setosa
1    	         4.9	        3.0	         1.4	        0.2	Iris-setosa
2    	         4.7	        3.2	         1.3	        0.2	Iris-setosa
3    	         4.6	        3.1	         1.5	        0.2	Iris-setosa
4    	         5.0	        3.6	         1.4	        0.2	Iris-setosa
5    	         5.4	        3.9	         1.7	        0.4	Iris-setosa
6    	         4.6	        3.4	         1.4	        0.3	Iris-setosa
7    	         5.0	        3.4	         1.5	        0.2	Iris-setosa
8    	         4.4	        2.9	         1.4	        0.2	Iris-setosa
9    	         4.9	        3.1	         1.5	        0.1	Iris-setosa
*/

// Print just 'sepal-length' and 'sepal-width'.
$dataset->print('sepal-length', 'sepal-width');
/*
     	sepal-length	sepal-width
_____	____________	___________
0    	         5.1	        3.5
1    	         4.9	        3.0
2    	         4.7	        3.2
3    	         4.6	        3.1
4    	         5.0	        3.6
5    	         5.4	        3.9
6    	         4.6	        3.4
7    	         5.0	        3.4
8    	         4.4	        2.9
9    	         4.9	        3.1
*/
```

##### Dataframe - Printing - Summary

Produce a formatted string containing a summary of the DataFrame,
including:
- row count
- standard deviation for each column
- average for each column
- minimum value for eachc column 
- quantiles for 25%, 50% and 75%
- maximum value for eachc column 

``` php
use sqonk\phext\datakit\Importer as import;

$columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$dataset = import::csv_dataframe('docs/iris.data', $columns)->head(10);

// Print the whole frame.
println($dataset->summary());
/*
     	    sepal-length	     sepal-width	   petal-length	      petal-width	class
_____	________________	________________	_______________	_________________	_____
count	              10	              10	             10	               10	   10
mean 	            4.86	            3.31	           1.45	             0.22	     
std  	0.27640549922171	0.29137604568667	0.1024695076596	0.074833147735479	    0
min  	             4.4	             2.9	            1.3	              0.1	     
25%  	           4.625	             3.1	            1.4	              0.2	     
50%  	             4.9	             3.3	            1.4	              0.2	     
75%  	               5	           3.475	            1.5	              0.2	     
max  	             5.4	             3.9	            1.7	              0.4	     
[10 rows x 5 columns]
*/
```

##### Dataframe - Printing - Shape

``` php
use sqonk\phext\datakit\Importer as import;

$columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$dataset = import::csv_dataframe('docs/iris.data', $columns)->head(10);

// Print the whole frame.
[$rows, $cols] = $dataset->shape();
println("$rows rows", "$cols columns");
// 10 rows 5 columns
```

##### Dataframe - Printing - Quantiles

``` php
use sqonk\phext\datakit\Importer as import;

$columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$dataset = import::csv_dataframe('docs/iris.data', $columns)->head(10);

// 75% quantile.
println($dataset->quantile(0.75));
/*
     	sepal-length	sepal-width	petal-length	petal-width
_____	____________	___________	____________	___________
0    	           5	      3.475	         1.5	        0.2
*/
```

##### Dataframe - Printing - Transformers

Transformers are used to visually modify the output of a column prior to printing. They do not modify the underlying data.

``` php
$dataset = dataframe([
    ['recorded' => '2019-08-20', 'name' => 'Falcon', 'Animal' => 'bird', 'Age' => 8, 'size' => 'big'],
    ['recorded' => '2020-01-08', 'name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
    ['recorded' => '2019-06-27', 'name' => 'Goat', 'Animal' => 'mammal', 'Age' => 12, 'size' => 'small'],
    ['recorded' => '2018-05-01', 'name' => 'Possum', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big']
]);
	

// Convert the date stamp into a unix time.
$dataset->transform(fn($v) => strtotime($v), 'recorded')->sort('recorded');

$dataset->print('name', 'recorded');
/*
** Timestamp values below is timezone dependant.

     	  name	  recorded
_____	______	__________
3    	Possum	1525096800
2    	  Goat	1561557600
0    	Falcon	1566223200
1    	Pigeon	1578402000
*/

// Add a value transformer to the 'recorded' column that will convert
// the timestamp back into a readable date whenever the frame is printed.
$dataset->apply_display_transformer(fn($v) => date('d/m/Y', $v), 'recorded');

$dataset->print('name', 'recorded');
/*
     	  name	  recorded
_____	______	__________
3    	Possum	01/05/2018
2    	  Goat	27/06/2019
0    	Falcon	20/08/2019
1    	Pigeon	08/01/2020
*/
```

##### Dataframe - Printing - OOB

Return a new DataFrame containing all values in the desired columns that are below or above the given limits.

``` php
use sqonk\phext\datakit\Importer as import;

$columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$dataset = import::csv_dataframe('docs/iris.data', $columns)->head(10);

$dataset->print('sepal-length');
/*
     	sepal-length
_____	____________
0    	         5.1
1    	         4.9
2    	         4.7
3    	         4.6
4    	         5.0
5    	         5.4
6    	         4.6
7    	         5.0
8    	         4.4
9    	         4.9
*/

$dataset->oob(4.5, 5.0, 'sepal-length')->print();
/*
     	      column	lower	upper
_____	____________	_____	_____
0    	sepal-length	     	  5.1
1    	sepal-length	     	     
2    	sepal-length	     	     
3    	sepal-length	     	     
4    	sepal-length	     	     
5    	sepal-length	     	  5.4
6    	sepal-length	     	     
7    	sepal-length	     	     
8    	sepal-length	  4.4	     
9    	sepal-length	     
*/
```

##### Dataframe - Printing - Gaps

Return a new 3-column DataFrame containing areas in the original where the running values in a column exceed a given amount.

Take the example below, which has a timestamp column where most of the entries are 5 minutes apart. Finding gaps in the running sequence can tell us if there are any missing entries and if so, how much of a gap between the two rows in question there is.

For this example there are 2 occurances of a data gap, one missing entry at the 2:15 mark, and 2 missing entries between 2:30 and 2:45.

``` php
// Initial frame with time stamps.
$dataset = dataframe([
    ['recorded' => '2020-04-10 14:00', 'name' => 'Falcon', 'Animal' => 'bird', 'Age' => 8, 'size' => 'big'],
    ['recorded' => '2020-04-10 14:05', 'name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
    ['recorded' => '2020-04-10 14:10', 'name' => 'Goat', 'Animal' => 'mammal', 'Age' => 12, 'size' => 'small'],
    ['recorded' => '2020-04-10 14:20', 'name' => 'Possum', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big'],
	['recorded' => '2020-04-10 14:26', 'name' => 'Snail', 'Animal' => 'insect', 'Age' => 0.3, 'size' => 'small'],
	['recorded' => '2020-04-10 14:30', 'name' => 'Ant', 'Animal' => 'insect', 'Age' => 0.1, 'size' => 'small'],
	['recorded' => '2020-04-10 14:45', 'name' => 'Cow', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big'],
	['recorded' => '2020-04-10 14:50', 'name' => 'Sheep', 'Animal' => 'mammal', 'Age' => 1, 'size' => 'big']
])
->transform(fn($v) => strtotime($v), 'recorded');

// Find any running sequences in the rows where the 'recorded' value is greater than 5 minutes.	
$gaps = $dataset->gaps(5 * 60, 'recorded')
	->apply_display_transformer(fn($v) => date('d/m/Y h:i a', $v), 'start', 'end');
println($gaps);
/*
     	              start	                end	segments
_____	___________________	___________________	________
0    	10/04/2020 02:10 pm	10/04/2020 02:20 pm	       1
1    	10/04/2020 02:30 pm	10/04/2020 02:45 pm	       2
*/
```

#### Dataframe - Calculations

``` php
use sqonk\phext\datakit\Importer as import;

$columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$dataset = import::csv_dataframe('docs/iris.data', $columns)->head(10);

// Absolute values..
println($dataset->abs(null, true));
/*
     	sepal-length	sepal-width	petal-length	petal-width	      class
_____	____________	___________	____________	___________	___________
0    	         5.1	        3.5	         1.4	        0.2	Iris-setosa
1    	         4.9	        3.0	         1.4	        0.2	Iris-setosa
2    	         4.7	        3.2	         1.3	        0.2	Iris-setosa
3    	         4.6	        3.1	         1.5	        0.2	Iris-setosa
4    	         5.0	        3.6	         1.4	        0.2	Iris-setosa
5    	         5.4	        3.9	         1.7	        0.4	Iris-setosa
6    	         4.6	        3.4	         1.4	        0.3	Iris-setosa
7    	         5.0	        3.4	         1.5	        0.2	Iris-setosa
8    	         4.4	        2.9	         1.4	        0.2	Iris-setosa
9    	         4.9	        3.1	         1.5	        0.1	Iris-setosa
*/

// Standard Deviation
println($dataset->std()->round(2));
/*
     	sepal-length	sepal-width	petal-length	petal-width	class
_____	____________	___________	____________	___________	_____
0    	        0.28	       0.29	         0.1	       0.07	    0
*/



// Sum
println($dataset->sum());
/*
     	sepal-length	sepal-width	petal-length	petal-width	      class
_____	____________	___________	____________	___________	___________
0    	        48.6	       33.1	        14.5	        2.2	Iris-setosa
*/

// Average, rounded to 3 decimal places.
println($dataset->avg()->round(3));
/*
     	sepal-length	sepal-width	petal-length	petal-width
_____	____________	___________	____________	___________
0    	        4.86	       3.31	        1.45	       0.22
*/

// Max value
println($dataset->max());
/*
     	sepal-length	sepal-width	petal-length	petal-width
_____	____________	___________	____________	___________
0    	         5.4	        3.9	         1.7	        0.4
*/

// Min value
println($dataset->min());
/*
     	sepal-length	sepal-width	petal-length	petal-width
_____	____________	___________	____________	___________
0    	         4.4	        2.9	         1.3	        0.1
*/

// Median value
println($dataset->median());
/*
     	sepal-length	sepal-width	petal-length	petal-width
_____	____________	___________	____________	___________
0    	         5.2	       3.75	        1.55	        0.3
*/

// Variance
println($dataset->variance()->round(2));
/*
     	sepal-length	sepal-width	petal-length	petal-width	class
_____	____________	___________	____________	___________	_____
0    	        0.08	       0.08	        0.01	       0.01	
*/


// Spearman Correlation (Pearson also available)
println($dataset->correlation('spearman', $columns)->round(3));
/*
     	    0	    1	    2	    3	    4
_____	_____	_____	_____	_____	_____
0    	    1	0.767	0.415	0.306	0.509
1    	0.767	    1	0.279	0.594	0.506
2    	0.415	0.279	    1	0.321	0.573
3    	0.306	0.594	0.321	    1	 0.67
4    	0.509	0.506	0.573	 0.67	    1
*/

// Product
println($dataset->product()->round(2));
/*
     	sepal-length	sepal-width	petal-length	petal-width
_____	____________	___________	____________	___________
0    	  7233730.13	  151979.71	       40.11	          0
*/

// Cumulative Sum
$dataset->cumsum()->round(2)->print();

/*
     	sepal-length	sepal-width	petal-length	petal-width	      class
_____	____________	___________	____________	___________	___________
0    	         5.1	        3.5	         1.4	        0.2	Iris-setosa
1    	          10	        6.5	         2.8	        0.4	Iris-setosa
2    	        14.7	        9.7	         4.1	        0.6	Iris-setosa
3    	        19.3	       12.8	         5.6	        0.8	Iris-setosa
4    	        24.3	       16.4	           7	          1	Iris-setosa
5    	        29.7	       20.3	         8.7	        1.4	Iris-setosa
6    	        34.3	       23.7	        10.1	        1.7	Iris-setosa
7    	        39.3	       27.1	        11.6	        1.9	Iris-setosa
8    	        43.7	         30	          13	        2.1	Iris-setosa
9    	        48.6	       33.1	        14.5	        2.2	Iris-setosa
*/

// Cumulative Max
$dataset->cummax()->round(2)->print();
/*
     	sepal-length	sepal-width	petal-length	petal-width	      class
_____	____________	___________	____________	___________	___________
0    	         5.1	        3.5	         1.4	        0.2	Iris-setosa
1    	         5.1	        3.5	         1.4	        0.2	Iris-setosa
2    	         5.1	        3.5	         1.4	        0.2	Iris-setosa
3    	         5.1	        3.5	         1.5	        0.2	Iris-setosa
4    	         5.1	        3.6	         1.5	        0.2	Iris-setosa
5    	         5.4	        3.9	         1.7	        0.4	Iris-setosa
6    	         5.4	        3.9	         1.7	        0.4	Iris-setosa
7    	         5.4	        3.9	         1.7	        0.4	Iris-setosa
8    	         5.4	        3.9	         1.7	        0.4	Iris-setosa
9    	         5.4	        3.9	         1.7	        0.4	Iris-setosa
*/

// Cumulative Min
$dataset->cummin()->round(2)->print();
/*
     	sepal-length	sepal-width	petal-length	petal-width	      class
_____	____________	___________	____________	___________	___________
0    	         5.1	        3.5	         1.4	        0.2	Iris-setosa
1    	         4.9	          3	         1.4	        0.2	Iris-setosa
2    	         4.7	          3	         1.3	        0.2	Iris-setosa
3    	         4.6	          3	         1.3	        0.2	Iris-setosa
4    	         4.6	          3	         1.3	        0.2	Iris-setosa
5    	         4.6	          3	         1.3	        0.2	Iris-setosa
6    	         4.6	          3	         1.3	        0.2	Iris-setosa
7    	         4.6	          3	         1.3	        0.2	Iris-setosa
8    	         4.4	        2.9	         1.3	        0.2	Iris-setosa
9    	         4.4	        2.9	         1.3	        0.1	Iris-setosa
*/

// Cumulative Product
$dataset->cumproduct()->round(2)->print();
/*
     	sepal-length	sepal-width	petal-length	petal-width	      class
_____	____________	___________	____________	___________	___________
0    	         5.1	        3.5	         1.4	        0.2	Iris-setosa
1    	       24.99	       10.5	        1.96	       0.04	Iris-setosa
2    	      117.45	       33.6	        2.55	       0.01	Iris-setosa
3    	      540.28	     104.16	        3.82	          0	Iris-setosa
4    	     2701.42	     374.98	        5.35	          0	Iris-setosa
5    	    14587.66	    1462.41	         9.1	          0	Iris-setosa
6    	    67103.25	    4972.18	       12.73	          0	Iris-setosa
7    	   335516.24	   16905.42	        19.1	          0	Iris-setosa
8    	  1476271.46	   49025.71	       26.74	          0	Iris-setosa
9    	  7233730.13	  151979.71	       40.11	          0	Iris-setosa
*/
```

#### Dataframe - Filtering and Sorting

##### Dataframe - Filtering and Sorting - Filtering

``` php
use sqonk\phext\datakit\Importer as import;

$columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$dataset = import::csv_dataframe('docs/iris.data', $columns);

// Do any of the value match.
println($dataset->any(4.6, 'sepal-length'));
// 1

// Do all values match.
println($dataset->all(4.6, 'sepal-length'));
// 0

// Find any rows where class is Iris-setosa OR sepal-length = 4.6
$filtered = $dataset->filter(function($value, $column, $index) {
	return ($column == 'class') ? $value == 'Iris-setosa' : $value == 4.6;
}, 'class', 'sepal-length');

$filtered->head(10)->print();
/*
     	sepal-length	sepal-width	petal-length	petal-width	      class
_____	____________	___________	____________	___________	___________
0    	         5.1	        3.5	         1.4	        0.2	Iris-setosa
1    	         4.9	        3.0	         1.4	        0.2	Iris-setosa
2    	         4.7	        3.2	         1.3	        0.2	Iris-setosa
3    	         4.6	        3.1	         1.5	        0.2	Iris-setosa
4    	         5.0	        3.6	         1.4	        0.2	Iris-setosa
5    	         5.4	        3.9	         1.7	        0.4	Iris-setosa
6    	         4.6	        3.4	         1.4	        0.3	Iris-setosa
7    	         5.0	        3.4	         1.5	        0.2	Iris-setosa
8    	         4.4	        2.9	         1.4	        0.2	Iris-setosa
9    	         4.9	        3.1	         1.5	        0.1	Iris-setosa
*/


// Find any rows where class is Iris-setosa AND sepal-length = 4.6
$filtered = $dataset->unanfilter(function($value, $column, $index) {
	return ($column == 'class') ? $value == 'Iris-setosa' : $value == 4.6;
}, 'class', 'sepal-length');

$filtered->print();
/*
     	sepal-length	sepal-width	petal-length	petal-width	      class
_____	____________	___________	____________	___________	___________
3    	         4.6	        3.1	         1.5	        0.2	Iris-setosa
6    	         4.6	        3.4	         1.4	        0.3	Iris-setosa
22   	         4.6	        3.6	         1.0	        0.2	Iris-setosa
47   	         4.6	        3.2	         1.4	        0.2	Iris-setosa
*/

// Find any rows where class is Iris-setosa OR sepal-length = 4.6.
// This is the same filter rules as the first example but demonstrates
// the use of the alternative filter method.
$filtered = $dataset->ufilter(function($row, $index) {
	return $row['class'] == 'Iris-setosa' or $row['sepal-length'] == 4.6;
});

$filtered->head(10)->print();
/*
     	sepal-length	sepal-width	petal-length	petal-width	      class
_____	____________	___________	____________	___________	___________
0    	         5.1	        3.5	         1.4	        0.2	Iris-setosa
1    	         4.9	        3.0	         1.4	        0.2	Iris-setosa
2    	         4.7	        3.2	         1.3	        0.2	Iris-setosa
3    	         4.6	        3.1	         1.5	        0.2	Iris-setosa
4    	         5.0	        3.6	         1.4	        0.2	Iris-setosa
5    	         5.4	        3.9	         1.7	        0.4	Iris-setosa
6    	         4.6	        3.4	         1.4	        0.3	Iris-setosa
7    	         5.0	        3.4	         1.5	        0.2	Iris-setosa
8    	         4.4	        2.9	         1.4	        0.2	Iris-setosa
9    	         4.9	        3.1	         1.5	        0.1	Iris-setosa
*/
```

##### Dataframe - Filtering and Sorting - Sorting

``` php
use sqonk\phext\datakit\Importer as import;

$columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$dataset = import::csv_dataframe('docs/iris.data', $columns)->slice(45, 10);


// Sort by 'class' column, then sepal-length in DESCENDING order.
// Passing ASCENDING or DESCENDING in the last parameter will dictate the direction of the sort.
$dataset->sort('class', 'sepal-length', DESCENDING)->print();
/*
     	sepal-length	sepal-width	petal-length	petal-width	          class
_____	____________	___________	____________	___________	_______________
50   	         7.0	        3.2	         4.7	        1.4	Iris-versicolor
52   	         6.9	        3.1	         4.9	        1.5	Iris-versicolor
54   	         6.5	        2.8	         4.6	        1.5	Iris-versicolor
51   	         6.4	        3.2	         4.5	        1.5	Iris-versicolor
53   	         5.5	        2.3	         4.0	        1.3	Iris-versicolor
48   	         5.3	        3.7	         1.5	        0.2	    Iris-setosa
46   	         5.1	        3.8	         1.6	        0.2	    Iris-setosa
49   	         5.0	        3.3	         1.4	        0.2	    Iris-setosa
45   	         4.8	        3.0	         1.4	        0.3	    Iris-setosa
47   	         4.6	        3.2	         1.4	        0.2	    Iris-setosa
*/

// Sort via a user-callback.
$dataset->usort(function($aval, $bval, $col) {
	return ($col == 'class') ? strcmp($aval, $bval) : $aval <=> $bval;
}, 'class', 'sepal-length')
	->print();
/*
     	sepal-length	sepal-width	petal-length	petal-width	          class
_____	____________	___________	____________	___________	_______________
47   	         4.6	        3.2	         1.4	        0.2	    Iris-setosa
45   	         4.8	        3.0	         1.4	        0.3	    Iris-setosa
49   	         5.0	        3.3	         1.4	        0.2	    Iris-setosa
46   	         5.1	        3.8	         1.6	        0.2	    Iris-setosa
48   	         5.3	        3.7	         1.5	        0.2	    Iris-setosa
53   	         5.5	        2.3	         4.0	        1.3	Iris-versicolor
51   	         6.4	        3.2	         4.5	        1.5	Iris-versicolor
54   	         6.5	        2.8	         4.6	        1.5	Iris-versicolor
52   	         6.9	        3.1	         4.9	        1.5	Iris-versicolor
50   	         7.0	        3.2	         4.7	        1.4	Iris-versicolor
*/
```

#### Dataframe - Transforms

##### Dataframe - Transforms - Pivot and Depivot

pivot() and devipot() generate a copy of the frame with the columns and row indexes rotated to become the other.

``` php
$dataset = dataframe([
    ['name' => 'Falcon', 'Animal' => 'bird', 'Age' => 8, 'size' => 'big'],
    ['name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
    ['name' => 'Goat', 'Animal' => 'mammal', 'Age' => 12, 'size' => 'small'],
    ['name' => 'Possum', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big']
]);
	
// rotate the frame, display the data via both the 'Animal' and 'Age' columns.
$p = $dataset->pivot('Animal', 'Age');
$p->print();
/*
Animal	     0	  bird
      	     1	  bird
      	     2	mammal
      	     3	mammal
Age   	     0	     8
      	     1	     4
      	     2	    12
      	     3	     2
*/

// deviot by Speed
$p->depivot('Age')->print();
/*
     	Age
_____	___
0    	  8
1    	  4
2    	 12
3    	  2
*/
```

##### Dataframe - Transforms - Groupby

groupby() splits the frame into many based on an individual column. This causes the creation of a GroupedDataFrame object that contains the many individual frames.

A GroupedDataFrame makes use of magic methods to allow the passthru of many of the standard DataFrame methods.

``` php
$dataset = dataframe([
    ['name' => 'Falcon', 'Animal' => 'bird', 'Age' => 8, 'size' => 'big'],
    ['name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
    ['name' => 'Goat', 'Animal' => 'mammal', 'Age' => 12, 'size' => 'small'],
    ['name' => 'Possum', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big']
]);
	
// Split the frame into 2 seperate tables, organised by the 'Animal' column.
$g = $dataset->groupby('Animal');
$g->print();
/*
     	  name	Animal	Age	 size
_____	______	______	___	_____
0    	Falcon	  bird	  8	  big
1    	Pigeon	  bird	  4	small

     	  name	Animal	Age	 size
_____	______	______	___	_____
2    	  Goat	mammal	 12	small
3    	Possum	mammal	  2	  big
*/

println('group count', count($g));
// group count 2


// Average out the Age of each frame and then recombine into a new one.
$g->avg()->combine()->print();
/*
      	Age
______	___
bird  	  6
mammal	  7
*/
```

##### Dataframe - Transforms - Transpose

This example demonstrates the use of transpose(), which can be used for shifting vertically aligned information in a 2-dimensional matrix into horizontal set.

This hypothetical dataset includes two movie characters and their amount of appearances in movies over the decades.

``` php
$dataset = dataframe([
    ['character' => 'Actor A', 'decade' => 1970, 'appearances' => 1],
    ['character' => 'Actor A', 'decade' => 1980, 'appearances' => 2],
    ['character' => 'Actor A', 'decade' => 1990, 'appearances' => 2],
    ['character' => 'Actor A', 'decade' => 2000, 'appearances' => 1],
    ['character' => 'Actor A', 'decade' => 2010, 'appearances' => 1],
    
    ['character' => 'Actor B', 'decade' => 1980, 'appearances' => 1],
    ['character' => 'Actor B', 'decade' => 1990, 'appearances' => 1],
    ['character' => 'Actor B', 'decade' => 2000, 'appearances' => 1],
]);
$dataset->print();
/*
     	character	decade	appearances
_____	_________	______	___________
0    	  Actor A	  1970	          1
1    	  Actor A	  1980	          2
2    	  Actor A	  1990	          2
3    	  Actor A	  2000	          1
4    	  Actor A	  2010	          1
5    	  Actor B	  1980	          1
6    	  Actor B	  1990	          1
7    	  Actor B	  2000	          1
*/

// Transform the matrix using transpose() so that each character becomes a column
// with their resulting appearances listed alongside the decade.
$transformed = $dataset->transpose('decade', ['character' => 'appearances']);
$transformed->print();
/*
     	decade	Actor A	Actor B
_____	______	_______	_______
0    	  1970	      1	       
1    	  1980	      2	      1
2    	  1990	      2	      1
3    	  2000	      1	      1
4    	  2010	      1	                 
*/
```

##### Dataframe - Transforms - Flatten

Flatten one or more columns into a native array.

``` php
use sqonk\phext\datakit\Importer as import;

$columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$dataset = import::csv_dataframe('docs/iris.data', $columns)->head(3);

$dataset->print();
/*
     	sepal-length	sepal-width	petal-length	petal-width	      class
_____	____________	___________	____________	___________	___________
0    	         5.1	        3.5	         1.4	        0.2	Iris-setosa
1    	         4.9	        3.0	         1.4	        0.2	Iris-setosa
2    	         4.7	        3.2	         1.3	        0.2	Iris-setosa
*/

$out = $dataset->flattened(false, 'sepal-length', 'sepal-width', 'class');
println($out);
/*
array (
  0 => 
  array (
    0 => '5.1',
    1 => '3.5',
    2 => 'Iris-setosa',
  ),
  1 => 
  array (
    0 => '4.9',
    1 => '3.0',
    2 => 'Iris-setosa',
  ),
  2 => 
  array (
    0 => '4.7',
    1 => '3.2',
    2 => 'Iris-setosa',
  ),
)
*/
```



#### Dataframe - Charting

##### Dataframe - Charting - Hist

Generate a histogram of one or more columns.

``` php
use sqonk\phext\datakit\Importer as import;

$columns = vector('sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class');

$dataset = import::csv_dataframe('../docs/iris.data', $columns->array());

$dataset->hist(['columns' => $columns->pop()->array()])->render(400, 300);
```

|                                                              |                                                              |                                                              |                                                              |
| ------------------------------------------------------------ | ------------------------------------------------------------ | ------------------------------------------------------------ | ------------------------------------------------------------ |
| ![Hist Sepal Length](https://sqonk.com/opensource/phext/datakit/docs/images/hist_sepal-length.png) | ![Hist Sepal Width](https://sqonk.com/opensource/phext/datakit/docs/images/hist_sepal-width.png) | ![Hist Petal Length](https://sqonk.com/opensource/phext/datakit/docs/images/hist_petal-length.png) | ![Hist Petal Width](https://sqonk.com/opensource/phext/datakit/docs/images/hist_petal-width.png) |



##### Dataframe - Charting - Box

Render a box plot of quantiles for each column.

``` php
use sqonk\phext\datakit\Importer as import;

$columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$dataset = import::csv_dataframe('docs/iris.data', $columns);

// Acquire the plot instance. 
$plot = $dataset->box('sepal-length', 'sepal-width', 'petal-length', 'petal-width');

// set the subfolder to output the rendered files to.
// Defaults to 'plots' subfolder, which will be created on output.
// $plot->output_path('path/to/subfolder');

// Output the chart with the given pixel dimensions. 
$plot->render(400, 300);
```
|                                                              |                                                              |                                                              |                                                              |
| ------------------------------------------------------------ | ------------------------------------------------------------ | ------------------------------------------------------------ | ------------------------------------------------------------ |
| ![Box Sepal Length](https://sqonk.com/opensource/phext/datakit/docs/images/box_sepal-length.png) | ![Box Sepal Width](https://sqonk.com/opensource/phext/datakit/docs/images/box_sepal-width.png) | ![Box Petal Length](https://sqonk.com/opensource/phext/datakit/docs/images/box_petal-length.png) | ![Box Petal Width](https://sqonk.com/opensource/phext/datakit/docs/images/box_petal-width.png) |







##### Dataframe - Charting - General

Produce a plot object (from the plotlib module) auto-configured to create an image-based graph of one or more columns.

Creating a plot from a DataFrame requires a plot type (string) and an array of configuration options.

```php
$columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$dataset = import::csv_dataframe('../docs/iris.data', $columns);

/* 
	Acquire a plot instance which will render all 4 numerical columns
	onto one chart.
*/
$plot = $dataset->plot('line', [
    'columns' => ['sepal-length', 'sepal-width', 'petal-length', 'petal-width'],
    'one' => true
]);

// set the subfolder to output the rendered files to.
// Defaults to 'plots' subfolder, which will be created on output.
// $plot->output_path('path/to/subfolder');

// Output the chart with the given pixel dimensions. 
$plot->render();
```

![Simple Line Plot](https://sqonk.com/opensource/phext/datakit/docs/images/sepal-length_sepal-width_petal-length_petal-width.png)

Configuration Options
*******

$options represent the chart configuation.
- title: 		Filename of the chart. Defaults to the chart type and series being plotted.
- _columns_: 	Array of the column names to produce charts for.
- _xcolumn_: 	A column name to use as the x-axis.
- _one_: 		If TRUE then all columns will be rendered onto one chart, when FALSE multiple charts are generated.
- _min_:		The minimum Y-value to render.
- _max_:		The maximum Y-value to render.
- _lines_:		Array of infinite lines to be drawn onto the chart. Each  item in the array is an associative array containing the the following options:
	- _direction_: Either VERTICAL or HORIZONTAL.
	- _value_: the numerical position on the respective axis that  the line will be rendered.
	- _color_: a colour name (e.g. red, blue etc) for the line colour. Default is red.
	- _width_: the stroke width of the line, default is 1.
- _labelangle_:	Angular rotation of the x-axis labels, default is 0.	
- _bars_:		A liniar array of values to represent an auxiliary/background bar chart dataset. This will plot on it's own Y axis.
- _barColor_:		The colour of the bars dataset, default is 'lightgray'.
- _barWidth_:		The width of each bar in the bars dataset, default is 7.
					

$type represents the type of chart (e.g line, box, bar etc). Possible values:
- _line_: 		line chart.
- _linefill_: 	line chart with filled area.
- _bar_:			bar chart.
- _barstacked_:	bar chart with each series stacked atop for each
				data point.
- _scatter_:		scatter chart.
- _box_:			Similar to a stock plot but with a fifth median value.


This example outputs a single chart with all four numerical columns rendered as line plots.

``` php
use sqonk\phext\datakit\Importer as import;

$columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$dataset = import::csv_dataframe('docs/iris.data', $columns);

// Acquire the plot instance. 
$plot = $dataset->plot('line', ['title' => 'test', 'columns' => $columns, 'one' => true]);

// set the subfolder to output the rendered files to.
// Defaults to 'plots' subfolder, which will be created on output.
// $plot->output_path('path/to/subfolder');

// Output the chart with the given pixel dimensions. 
$plot->render(700, 500);
```

#### Dataframe - Export

The export() method will output the data to a delimitered file, including column headers.

``` php
use sqonk\phext\datakit\Importer as import;

$columns = ['sepal-length', 'sepal-width', 'petal-length', 'petal-width', 'class'];

$dataset = import::csv_dataframe('docs/iris.data', $columns);

$dataset->export('exported.csv', ['class', 'sepal-length', 'sepal-width']);
```

### Defined Constants

- ASCENDING / DESCENDING: Used for sorting methods in Vector and DataFrame to indicate sorting direction.
- FIRST_ROW / LAST_ROW: Used for fetching the first or last row in a DataFrame.
- OOB_UPPER / OOB_LOWER / OOB_ALL: Used with DataFrame for acquiring 'out of bounds' regions.



### Importer Methods

A selection of routines for importing data from various static sources such as files.

```php
/*
    Import a CSV from a string containing the data.

    Provides a fast and convienient way of importing data from CSV formats. Each row
    is returned to your callback method as an array of values, where you may do
    as you desire with it. Alternatively if you pass in NULL as the callback then
    all the data will be returned as an array.

    Your callback method should be in the format of:

    function myCSVCallback($row)

    where $row is an array of the values retrieved from the current row in the CSV. When the
    first row is indicated as containing the column headers then the supplied array will
    be indexed with the column headers as the keys. 

    In the cases were the CSV has no column headers then the supplied array will be in simple
    sequential order.

    @param $callback                A callback method to process each row. Pass in NULL to have the data returned at the end.
    @param $data                    The CSV data in string format.
    @param $headersAreFirstRow      TRUE or FALSE, where are not the first row contains headers.
    @param $customHeaders           If the headers are not in the first row then you may optionally pass in an array of headers to be used in place.

    @returns                        TRUE upon successful completion or the imported data array when no callback is being used. 
*/
static public function csv_data(?callable $callback, string $data, bool $headersAreFirstRow, $customHeaders = null);

/*
    Import a CSV from a local file on disk or a URL.

    Provides a fast and convienient way of importing data from CSV formats. Each row
    is returned to your callback method as an array of values, where you may do 
    as you desire with it. Alternatively if you pass in NULL as the callback then
    all the data will be returned as an array.

    Your callback method should be in the format of:

    function myCSVCallback($row)

    where $row is an array of the values retrieved from the current row in the CSV. When the
    first row is indicated as containing the column headers then the supplied array will
    be indexed with the column headers as the keys. 

    In the cases were the CSV has no column headers then the supplied array will be in simple
    sequential order.

    @param $callback                A callback method to process each row. Pass in NULL to have the data returned at the end.
    @param $filePath                Path or URL to the file.
    @param $headersAreFirstRow      TRUE or FALSE, where are not the first row contains headers.
    @param $customHeaders           If the headers are not in the first row then you may optionally pass in an array of headers to be used in place.
    @param $skipRows                Skip over a specified number of rows at the start. Defaults to 0.

    @returns                        TRUE upon successful completion or the imported data array when no callback is being used. 

    This method will throw an exception if an error is encountered at any point in the process.
*/
static public function csv_file(?callable $callback, string $filePath, bool $headersAreFirstRow, $customHeaders = null, int $skipRows = 0);

/*
    Import a CSV from a local file on disk or a URL and yield one row at a time
    as a generator to an outer loop.
 
    Each yielded row is an array of the values retrieved from the current row in 
    the CSV. When the first row is indicated as containing the column headers then 
    the supplied array will be indexed with the column headers as the keys. 

    In the cases were the CSV has no column headers then the supplied array will be in simple
    sequential order.

    @param $filePath                Path or URL to the file.
    @param $headersAreFirstRow      TRUE or FALSE, where are not the first row contains headers.
    @param $customHeaders           If the headers are not in the first row then you may optionally 
                                    pass in an array of headers to be used in place.
    @param $skipRows                Skip over a specified number of rows at the start. Defaults to 0.

    This method will throw an exception if an error is encountered at any point in the process.
*/
static public function yield_csv(string $filePath, bool $headersAreFirstRow, ?array $customHeaders = null, int $skipRows = 0)

/*
	Import a CSV directly into a DataFrame object in the most memory efficient way.

	In the cases were the CSV has no column headers then the supplied array will be in simple
sequential order.

	@param $filePath      Path or URL to the CSV file.
	@param $colSettings   When TRUE, will take the first row as the headers. When an array
                        is supplied then the array will be used as the column headers.
                        Passing FALSE or any other value will result in sequential column
                        headers.

	@param $skipRows			Skip over a specified number of rows at the start. Defaults to 0.

	This method will throw an exception if an error is encountered at any point in the process.
*/
static public function csv_dataframe(string $filepath, $colSettings = false, int $skipRows = 0)

/*
    Split a string of raw data down into rows and columns.

    Each row is returned to your callback method as an array of values, where you may do
    as you desire with it.

    Your callback method should be in the format of:

    function myCallback($row)

    where $row is an array of the values retrieved from the current row or line in the data. The supplied 
    array will be in simple sequential order.

    @param $callback                A callback method to process each row.
    @param $data                    The data to be processed.
    @param $itemDelimiter           The token used to split each row into individual items.
    @param $lineDelimiter           The line ending used to split the data into seperate rows or lines.
    @param $headersAreFirstRow      TRUE or FALSE, where are not the first row contains headers.
    @param $customHeaders           If the headers are not in the first row then you may optionally pass in an array of headers to be used in place.

    @returns                        TRUE upon successful completion. 

    This method will throw an exception if an error is encountered at any point in the process or the provided data
    can not be broken down into lines using the provided line ending character.
*/
static public function delimitered_data(callable $callback, string $data, string $itemDelimiter, string $lineDelimiter = "\n", bool $headersAreFirstRow = false, $customHeaders = null);
```

### SMA Methods

A simple class for management of a Simple Moving Average. It works by alternating between adding new values to the array and calculating the current average.

Adheres to interfaces:
* _Stringable_

```php
/*
    Construct a new SMA with the specified maximum number of values.
*/
public function __construct(int $maxItems);

/* 
    Add a new value to the SMA. The value must be numerical in nature.
*/
public function add($value);

/*
    Return the calculated result of the SMA as it currently stands. You can optionally  
    pass in a value to $precision to control the amount of decimal places that the result 
    is rounded to.
*/
public function result(?int $precision = null);
```

### EMA Methods

A simple class for management of a Exponential Moving Average. It works by alternating between adding new values to the array and calculating the current average.

Adheres to interfaces:
* _Stringable_

```php
/*
    Construct a new EMA with the specified maximum number of values.
*/
public function __construct(int $maxItems);

/* 
    Add a new value to the EMA. The value must be numerical in nature.
*/
public function add($value);

/*
    Return the calculated result of the EMA as it currently stands. You can 
    optionally pass in a value to $precision to control the amount of decimal 
    places that the result is rounded to.
*/
public function result(?int $precision = null);
```

### DOMScraper Methods

A class for automatically navigating and extracting information out of a DOMDocument. 

It works by providing it the HTML/XML content and then calling <code>traverse</code>. The method will sequentially transcend each element provided, eventually dispaching the nodes to your callback when the last item type in the element array has been reached.

```php
/* 
    Create a new scraper using the provided text content. The contents
    should be either XML or HTML.
*/
public function __construct(string $contents);

// Return the DOMDocument object.
public function dom();

/*
    Traverse a hierarchal series of elements in the document, drilling down to the final set
    and providing them back to your program for processing.

    Elements array should be in format of: 
    [   'type' => 'id|tag|class', 
        'name' => 'elementID or tag type', 
        (optional) 'item' => int (used to only work said index in resulting array).
    ]

    e.g.
    traverse([
        ['type' => 'id', 'name' => 'container'], # fetch DIV called container
        ['type' => 'tag', 'name' => 'table', 'item' => 0] # get the first table inside 'container'
        ['type' => 'tag' 'name' => 'tr'] # fetch all rows inside the first table.
    ]);

    In this example the table rows found from the last definition in the elements array would
    be passed to your callback, which takes one parameter only.

    @returns array [BOOL pass, STRING errorMessage]. 

    Returns true if 1 or more items were found and passed to the callback, false otherwise.                   
*/
public function traverse(array $elements, callable $callback, $current = null);
```

### Vector Methods

A class to add both object orientation and utility methods to native arrays, enabling easier to write and easier to read code.

In particular it sports a variety of basic mathematical and statistical functions.


Adheres to interfaces:
* _Stringable_
* _ArrayAccess_
* _Countable_
* _IteratorAggregate_

```php
/*
    Contruct a new vector with the provided array.
*/
public function __construct(array $startingArray = []);


// Returns a copy of the vector as a native array.
public function array();

// Return the number of elements in the array.
public function count();

/* 
    Set a rolling capacity limit on the vector. Once set, old values 
    will be shifted off of the beginning to make room for new 
    values once the capacity is reached.

    Setting the limit to NULL will remove the constraint altogether, 
    which is the default.
*/
public function constrain(?int $limit);

// Add one or more elements to the end of the vector.
public function add(...$values);

/*
    Set an element in the array to the provided key/index.
*/
public function set($key, $value);

/*
    Add one or more elements to the start of the vector. If a constraint
    is set then excess elements will be removed from the end.
*/
public function prepend(...$values);

/* 
    Add a value supplied by the callback to the end of the vector a set
    number of times.

    The callback should take no parameters.
*/
public function fill(int $amount, callable $callback);

/* 
    Add a value supplied by the callback to the start of the vector a set
    number of times.

    The callback should take no parameters.
*/
public function prefill(int $amount, callable $callback);

/* 
    Return the value for a specified key. If the key is not present
    in the array then the default value is returned instead.
*/
public function get($key, $default = null);

/*
    Remove one or more elements from the array.
*/
public function remove(...$keys);

// Remove all elements from the array.
public function clear();

/* 
    Returns TRUE if all the specified keys are present within the vector, FALSE
    otherwise.
*/
public function isset(...$keys);

// Return all indexes of the array.
public function keys();

// Returns TRUE if there are 0 elements in the array, FALSE otherwise.
public function empty();

/*
    Return all values for a given key in the vector. This assumes all elements
    inside of the vector are an array or object.

    If no key is provided then it will return all primary values in the vector.    
*/
public function values($key = null);


/*
    Return a new vector containing all unique values in the current.

    If $key is provided then the operation is performed on the values resulting
    from looking up $key on each element in the vector. This assumes all elements
    inside of the vector are an array or object.
*/
public function unique($key = null);

/*
    Produces a new vector containing counts for the number of times each value
    occurs in the array.

    If $key is provided then the operation is performed on the values resulting
    from looking up $key on each element in the vector, assuming all elements
    inside of the vector are an array or object.
*/
public function frequency($key = null);

/*
    Remove all entries where the values corresponding to 'empties' are omitted.
*/
public function prune($empties = '');

// Return the first object in the array or null if array is empty.
public function first();

// Return the last object in the array or null if array is empty.
public function last();

/*
    Return the object closest to the middle of the array. 
    - If the array is empty, returns null.
    - If the array has less than 3 items, then return the first or last item depending 
    on the value of $weightedToFront.
    - Otherwise return the object closest to the centre. When dealing with arrays containing
    an even number of items then it will use the value of $weightedToFront to determine if it
    picks the item closer to the start or closer to the end.

    @param $array               The array containing the items.
    @param $weightedToFront     TRUE to favour centre items closer to the start of the array 
                                and FALSE to prefer items closer to the end.
*/
public function middle(bool $weightedToFront = true);

/*
    Randomly choose and return an item from the vector.
*/
public function choose();

/*
    Returns the first item in the vector found in the heystack or FALSE if none are found.
*/
public function occurs_in(string $heystack);

/*
    Returns TRUE if any of the values within the vector are equal to the value
    provided, FALSE otherwise.

    A callback may be provided as the match to perform more complex testing.

    Callback format: myFunc($value) -> bool

    For basic (non-callback) matches, setting $strict to TRUE will enforce 
    type-safe comparisons.
*/
public function any($match, $strict = false);

/*
    Returns TRUE if all of the values within the vector are equal to the value
    provided, FALSE otherwise.

    A callback may be provided as the match to perform more complex testing.

    Callback format: myFunc($value) -> bool

    For basic (non-callback) matches, setting $strict to TRUE will enforce 
    type-safe comparisons.
*/
public function all($match, $strict = false);

/*
    Filter the contents of the vector using the provided callback. 

    ARRAY_FILTER_USE_BOTH is provided as the flag to array_filter() so that
    your callback may optionally take the key as the second parameter.
*/
public function filter(callable $callback);

/*
    Filter the vector based on the contents of one or more vectors or arrays and return a 
    new vector containing just the elements that were deemed to exist in all.
*/
public function intersect(...$otherArrays);

/*
    Filter the vector based on the contents of one or more arrays and return a 
    new vector containing just the elements that were deemed not to be present 
    in all.
*/
public function diff(...$otherArrays);

/* 
    Return a copy of the vector containing only the values for the specified keys,
    with index association being maintained.

    This method is primarily designed for non-sequential vectors but can also be used
    with sequential 2-dimensional vectors. If the vector is sequential and the elements
    contained within are arrays or vectors then the operation is performed on them, 
    otherwise it is performed on the top level of the vector.

    It should be noted that if a key is not  present in the current vector then it will 
    not be present in the resulting vector.
*/
public function only_keys(...$keys);

/* 
    Search the array for the given needle (subject). This function is an
    alias of Vector::any().
*/
public function contains($needle);

// Determines if the array ends with the needle.
public function ends_with($needle);

// Determines if the array starts with the needle.
public function starts_with($needle);

// Trim all entries in the array (assumes all entries are strings)/
public function trim();

/*
    Join all elements in the vector into a string using the supplied delimier
    as the seperator.

    This assumes all elements in the vector are capable of being cast to a 
    string.
*/
public function implode(string $delimier = '', string $subDelimiter = '');

/*
    Implode the vector using the desired delimiter and subdelimiter. 

    This method is primarily intended for non-senquential/associative vectors
    and differs from the standard implode in that it will only implode the values
    associated with the specified keys/indexes.
*/
public function implode_only(string $delimier, array $keys, string $subDelimiter = '');

/*
    Apply a callback function to the vector. This version will optionally
    supply the corresponding index/key of the value when needed.

    Callback format: myFunc($value, $index) -> mixed
*/
public function map(callable $callback);

/* 
    Split the array into batches each containing a total specified
    by $itemsPerBatch. 

    The final batch may contain less than the specified batch count if 
    the array total does not divide evenly.
*/
public function chunk(int $itemsPerBatch);

/*
    Pad vector to the specified length with a value. If $count is positive then 
    the array is padded on the right, if it's negative then on the left. If the 
    absolute value of $count is less than or equal to the length of the array  
    then no padding takes place.
*/
public function pad(int $count, $value);

/* 
    Shorten the vector by removing elements off the end of the array to the number 
    specified in $amount. If $returnRemoved is TRUE then the items removed will
    be returned, otherwise it returns a reference to itself for chaining purposes.
*/
public function pop(int $amount = 1, bool $returnRemoved = false);

/* 
    Modify the vector by removing elements off the beginning of the array to the  
    number specified in $amount and return a vector containing the items removed.
    If $returnRemoved is TRUE then the items removed will be returned, otherwise 
    it returns a reference to itself for chaining purposes
*/
public function shift(int $amount = 1, bool $returnRemoved = false);

/*
    Transform a set of rows and columns with vertical data into a horizontal configuration
    where the resulting array contains a column for each different value for the given
    fields in the merge map (associative array).

    The group key is used to specifiy which field in the array will be used to flatten
    multiple rows into one.

    For example, if you had a result set that contained a 'type' field, a corresponding
    'reading' field and a 'time' field (used as the group key) then this method would 
    merge all rows containing the same time value into a matrix containing as
    many columns as there are differing values for the type field, with each column
    containing the corresponding value from the 'reading' field.
*/
public function transpose(string $groupKey, array $mergeMap);

/*
    Transfom the vector (assuming it is a flat array of elements) and split them into a 
    tree of vectors based on the keys passed in.

    The vector will be re-sorted by the same order as the set of keys being used. If only 
    one key is required to split the array then a singular string may be provided, otherwise 
    pass in an array.

    Unless $keepEmptyKeys is set to TRUE then any key values that are empty will be omitted.
*/
public function groupby($keys, bool $keepEmptyKeys = false);

/*
  Sort the vector in either ASCENDING or DESCENDING direction. If the
  vector is associative then index association is maintained, otherwise
  new indexes are generated.

  Refer to the PHP documentation for all possible values on the $flags.
*/
public function sort(int $dir = ASCENDING, int $flags = SORT_REGULAR);

/*
  Sort the vector by the indexes in either ASCENDING or DESCENDING direction. 

  Refer to the PHP documentation for all possible values on the $flags.
*/
public function ksort(int $dir = ASCENDING, int $flags = SORT_REGULAR)

/*
  Sort the vector based on the value of a key inside of the sub-array/object.

  $key can be a singular string, specifying one key, or an array of keys.

  If the vector is associative then index association is maintained, otherwise new  
  indexes are generated.

  NOTE: This method is designed for multi-dimensional vectors or vectors of objects.

  See 'ksort' for sorting the vector based on the array indexes.
*/
public function keyed_sort($key);

/* 
    Return a copy of the vector only containing the number
    of rows from the start as specified by $count.
*/
public function head(int $count);

/* 
    Return a copy of the vector only containing the number
    of rows from the end as specified by $count.
*/
public function tail(int $count);

/* 
    Return a copy of the vector only containing the the rows
    starting from $start through to the given length.
*/
public function slice(int $start, ?int $length = null);

/*
    Return a copy of the vector containing a random subset of the elements. The minimum and 
    maximum values can be supplied to focus the random sample to a more constrained subset. 
*/
public function sample(int $minimum, ?int $maximum = null);

/*
    Provide a maximum or minimum (or both) constraint for the values in the vector.

    If a value exceeds that constraint then it will be set to the constraint.

    If either the lower or upper constraint is not needed then passing in null will 
    ignore it.

    If $inPlace is TRUE then this operation modifies this vector otherwise a copy is 
    returned.
*/
public function clip($lower, $upper, bool $inplace = false);

/*
    Reverse the current order of the values within the vector. If $inplace 
		is TRUE then this method will modify the existing vector instead of 
		returning a copy.
*/
public function reverse(bool $inplace = false)
      
/*
    Swap the keys and values within the vector. If $inplace is TRUE then
    this method will modify the existing vector instead of returning a
    copy.
*/
public function flip(bool $inplace = false);

/*
    Compute a sum of the values within the vector.
*/
public function sum();

/*
    Compute the average of the values within the vector.
*/
public function avg();

/*
    Return the maximum value present within the vector.
*/
public function max();

/*
    Return the minimum value present within the vector.
*/
public function min();

/*
    Find the median value within the vector.
*/
public function median();

/*
    Compute the product of the values within the vector.
*/
public function product();

/*
    Compute a cumulative sum of the values within the vector.
*/
public function cumsum();

/*
    Compute the cumulative maximum value within the vector.
*/
public function cummax();

/*
    Compute the cumulative minimum value within the vector.
*/
public function cummin();

/*
    Compute the cumulative product of the values within the vector.
*/
public function cumproduct();

/*
    Compute the variance of values within the vector.
*/
public function variance();

/*
    Iteratively reduce the vector to a single value using a callback 
    function. 

    If the optional $initial is available, it will be used at the beginning 
    of the process, or as a final result in case the vector is empty.

    Callback format: myFunc( $carry, $item ) : mixed

    Returns the resulting value.
*/
public function reduce(callable $callback, $initial = null);

/*
    Round all values in the vector up or down to the given decimal point precesion.
*/
public function round(int $precision, int $mode = PHP_ROUND_HALF_UP);
```

### DataFrame Methods

The DataFrame is a class inspired by, and loosely based off of, a class by the same name from the Pandas library in Python. It specialises in working with 2 dimensional arrays (rows and columns) that may originate from data sources such as CSV files or data fetched from a relational database.

Various basic statistical and mathematical functions are provided as well numerous methods for transforming and manipulating the underlying data and presentation thereof.

Adheres to interfaces:
* _Stringable_
* _ArrayAccess_
* _Countable_
* _IteratorAggregate_

```php
/*
    Construct a new dataframe with the provided data. You may optionally
    provided the set of column headers in the second parameter. If you 
    choose to do this then they should match the keys in the array.

    NOTE: The provided array must have at least one element/row and
    must also be 2-dimensional in structure.
*/
public function __construct(array $data, array $headers = null);

// Produce an exact replica of the dataframe.
public function copy();
/*
    Produce a copy of the dataframe consisting of only the supplied data. All other
    information such as transfomers and header settings remain the same.
*/
public function clone($data);

/* 
    Whether or not the DataFrame should display the column
    headers when it is printed. The default is TRUE.
*/
public function display_headers($display = null);

/* 
    Whether or not the DataFrame should display the row
    indexes that sequentially numerical when it is printed. 

    The default is TRUE.

    This is automatically disabled for pivoted DataFrames.
*/
public function display_generic_indexes($display = null);

/* 
    Set or get the column header currently or to be used
    as the row indexes.

    ** You should not need to set this. 

    See reindex_rows_with_column() instead.
*/
public function index($indexHeader = null);

/*
    Used to set or get the full list of display transformers.

    ** Used internally. You should not need to call this
    function under normal circumstances. 

    See apply_display_transformer() instead.
*/
public function transformers($transformers = null);

/* 
    Returns TRUE if and only if all values within the given column
    contain a valid number.
*/
public function column_is_numeric(string $column);

/* 
    Return the associative array containing all the data within
    the DataFrame.
*/
public function data();

/*
    Flatten the DataFrame into a native array.

    $includeIndex:     If TRUE then use the DataFrame indexes as the 
                    keys in the array.
    $columns        One or more columns that should be used in the
                    resulting array, all columns if null is supplied.
*/
public function flattened(bool $includeIndex = true, ...$columns);

// Return the row at $index.
public function row($index);

// Return an array of all the current row indexes.
public function indexes();

// All column headers currently in the DataFrame.
public function headers();

/* 
    Return a copy of the DataFrame only containing the number
    of rows from the start as specified by $count.
*/
public function head(int $count);

/* 
    Return a copy of the DataFrame only containing the number
    of rows from the end as specified by $count.
*/
public function tail(int $count);

/* 
    Return a copy of the DataFrame only containing the the rows
    starting from $start through to the given length.
*/
public function slice(int $start, ?int $length = null);

/*
    Return a copy of the DataFrame containing a random
    subset of the rows. The minimum and maximum values
    can be supplied to focus the random sample to a 
    more constrained subset.
*/
public function sample(int $minimum, ?int $maximum = null);

/*
    Change the name of a column within the DataFrame. If $inPlace
    is TRUE then this operation modifies the receiver otherwise
    a copy is returned.
*/
public function change_header(string $column, string $newName, bool $inPlace = false);

/*
    Reindex the DataFrame using the provided labels. If $inPlace
    is TRUE then this operation modifies the receiver otherwise
    a copy is returned.
*/
public function reindex_rows(array $labels, bool $inPlace = false);

/*
    Push one of the columns out to become the row index. If $inPlace
    is TRUE then this operation modifies the receiver otherwise
    a copy is returned.
*/
public function reindex_rows_with_column(string $column, bool $inPlace = false);

/*
    Filter the DataFrame using the provided callback and one or 
    more columns. If no columns are specified then the operation
    applies to all.

    Callback format: myFunc($value, $column, $rowIndex) -> bool

    For a row to make it into the filtered set then only ONE
    of the columns need to equate to true from the callback.
*/
public function filter(callable $callback, ...$columns);

/*
    Filter the DataFrame using the provided callback and one or 
    more columns. If no columns are specified then the operation
    applies to all.

    Callback format: myFunc($value, $column, $rowIndex) -> bool

    For a row to make it into the filtered set then ALL
    of the columns need to equate to true from the callback.
*/
public function unanfilter(callable $callback, ...$columns);

/*
    Filter the DataFrame using the provided callback and one or 
    more columns. If no columns are specified then the operation
    applies to all.

    Callback format: myFunc($row, $rowIndex) -> bool

    This function differs from filter() and unanfilter() in that
    it passes the whole row to the callback. This is useful
    if your condition of inclusion requires cross comparing
    data across columns within the row.
*/
public function ufilter(callable $callback);

/*
    Sort the DataFrame via one or more columns.
    
    If the last parameter passed in is either TRUE or FALSE
    then it will determine the direction in which the dataframe
    is ordered. The default is ascending (TRUE).
*/
public function sort(...$columns);

/*
    Sort the DataFrame using a callback and one or more columns.

    Callback format: myFunc($value1, $value2, $column) -> bool
*/
public function usort(callable $callback, ...$columns);

// Return an array containing both the number of rows and columns.
public function shape();


/* 
    If a column is specified then return the number of rows
    containing a value for it.

    If no column is given then return a new DataFrame containing
    the counts for all columns.
*/
public function size($column = null);

// Return the number of rows.
public function count();

/*
    Return a two-dimensional native array containing the
    values for the given columns of all rows in the dataframe.    

    If no columns are specified then all are used.
*/
public function matrix(...$columns);

/* 
    Return all values for the given column. If $filterNAN is
    TRUE then omit values that are NULL.
*/
public function values($columns = null, bool $filterNAN = true);

/*
    Produce a formatted string, suitable for outputing to
    the commandline or browser, detailing all rows and
    the desired columns. If no columns are specified then 
    all columns are used.
*/
public function report(...$columns);

/* 
    Print to stdout the report for this DataFrame.

    See: report()
*/
public function print(...$columns);

/*
    Provide a maximum or minimum (or both) constraint for the values on column.

    If a row's value for the column exceeds that constraint then it will be set
    to the constraint.

    If either the lower or upper constraint is not needed then passing in
    null will ignore it.

    If no column is specified then the constraints apply to all columns.

    If $inPlace is TRUE then this operation modifies the receiver otherwise
    a copy is returned.
*/
public function clip($lower, $upper, string $column = null, bool $inplace = false);

/*
    Remove any rows where the value of the provided column exeeds the provided
    lower or upper boundary, for a given column.

    If either the lower or upper constraint is not needed then passing in
    null will ignore it.

    If no column is specified then the filter applies to all columns.

    If $inPlace is TRUE then this operation modifies the receiver otherwise
    a copy is returned.
*/
public function prune($lower, $upper, $column = null, $inplace = false);

/*
    Return a new DataFrame containing the rows where the values of the
    given column exceed a lower and/or upper boundary.

    If either the lower or upper constraint is not needed then passing in
    null will ignore it.

    If no column is specified then the filter applies to all columns.
*/
public function oob($lower, $upper, $column = null);

/*
    Return a new 2-column DataFrame containing both the start and endpoints
    where valus for a specific column exceed a given threshold.

    $direction can be OOB_LOWER, OOB_UPPER or OOB_ALL to dertermining if
    the threshhold is calculated as a minimum boundary, maximum boundary or
    either.

    Where oob() simply returns all the rows that exceed the threshold, this
    method will return a DataFrame of regions, where the start and end
    values refer to the row indexes of the current DataFrame.
*/
public function oob_region($theshhold, $direction, string $column);

/*
    Return a new 3-column DataFrame containing areas in the current where
    running values in a column exceed the given amount.

    For example, if you have a column of timestamps and those timestamps
    typically increase by N minutes per row, then this method can be used to 
    find possible missing rows where the jump time is greater than the expected
    amount.

    For every row where the given amount is exceeded, a row in the resulting
    DataFrame will exist where 'start' and 'end' list the values where the
    gap was found. A third column 'segments' details how many multiples of
    the amount exist between the values.

    Providing a column name to $resultColumn allows you to perform the
    comparison in one column while filling the resulting DataFrame with
    referenced values from another column.
*/
public function gaps($amount, string $usingColumn, string $resultColumn = '');

/*
	Produces a new DataFrame containing counts for the number of times each value 
	occurs in the given column.
*/
public function frequency(string $column);
    
/*
    Returns TRUE if ANY of the rows for a given column match
    the given value.

    If no column is specified then the the check runs over
    all columns.
*/
public function any($value, string $column = null);

/*
    Returns TRUE if ALL of the rows for a given column match
    the given value.

    If no column is specified then the the check runs over
    all columns.
*/
public function all($value, $column = null);

/*
    Convert all values in a given column to their absolute
    value.

    If no column is specified then the the operation runs over
    all columns.

    If $inPlace is TRUE then this operation modifies the current
    DataFrame, otherwise a copy is returned.
*/
public function abs($column = null, $inplace = false);

/*
    Compute a standard deviation of one or more columns.

    If no column is specified then the the operation runs over
    all columns.

    If exactly one column is supplied then a single value is 
    returned, otherwise a DataFrame of 1 value per column is
    produced.

    $sample is passed through to the standard deviation calculation
    to determine how the result is producted.
*/
public function std(bool $sample = false, ...$columns);

/*
    Compute a sum of one or more columns.

    If no column is specified then the the operation runs over
    all columns.

    If exactly one column is supplied then a single value is 
    returned, otherwise a DataFrame of 1 value per column is
    produced.
*/
public function sum(...$columns);

/*
    Compute a cumulative sum of one or more columns.

    If no column is specified then the the operation runs over
    all columns.

    If exactly one column is supplied then a single value is 
    returned, otherwise a DataFrame of 1 value per column is
    produced.
*/
public function cumsum(...$columns);

/*
    Compute the average of one or more columns.

    If no column is specified then the the operation runs over
    all columns.

    If exactly one column is supplied then a single value is 
    returned, otherwise a DataFrame of 1 value per column is
    produced.
*/
public function avg(...$columns);

/*
    Return the maximum value present for one or more columns.

    If no column is specified then the the operation runs over
    all columns.

    If exactly one column is supplied then a single value is 
    returned, otherwise a DataFrame of 1 value per column is
    produced.
*/
public function max(...$columns);

/*
    Return the minimum value present for one or more columns.

    If no column is specified then the the operation runs over
    all columns.

    If exactly one column is supplied then a single value is 
    returned, otherwise a DataFrame of 1 value per column is
    produced.
*/
public function min(...$columns);

/*
    Compute the cumulative maximum value for one or more
    columns.

    If no column is specified then the the operation runs over
    all columns.

    If exactly one column is supplied then a single value is 
    returned, otherwise a DataFrame of 1 value per column is
    produced.
*/
public function cummax(...$columns);

/*
    Compute the cumulative minimum value for one or more
    columns.

    If no column is specified then the the operation runs over
    all columns.

    If exactly one column is supplied then a single value is 
    returned, otherwise a DataFrame of 1 value per column is
    produced.
*/
public function cummin(...$columns);

/*
    Find the median value for one or more columns.

    If no column is specified then the the operation runs over
    all columns.

    If exactly one column is supplied then a single value is 
    returned, otherwise a DataFrame of 1 value per column is
    produced.
*/
public function median(...$columns);

/*
    Compute the product for one or more columns.

    If no column is specified then the the operation runs over
    all columns.

    If exactly one column is supplied then a single value is 
    returned, otherwise a DataFrame of 1 value per column is
    produced.
*/
public function product(...$columns);

/*
    Compute the cumulative product for one or more columns.

    If no column is specified then the the operation runs over
    all columns.

    If exactly one column is supplied then a single value is 
    returned, otherwise a DataFrame of 1 value per column is
    produced.
*/
public function cumproduct(...$columns);

/*
    Compute the variance for one or more columns.

    If no column is specified then the the operation runs over
    all columns.

    If exactly one column is supplied then a single value is 
    returned, otherwise a DataFrame of 1 value per column is
    produced.
*/
public function variance(...$columns);

/*
    Compute the value for a given quantile for one or more columns.

    If no column is specified then the the operation runs over
    all columns.

    If exactly one column is supplied then a single value is 
    returned, otherwise a DataFrame of 1 value per column is
    produced.
*/
public function quantile($quantile, $column = null);

/*
    Round all values in the DataFrame up or down to the given
    decimal point precesion.
*/
public function round($precision, int $mode = PHP_ROUND_HALF_UP);

/*
    Run a correlation over one or more columns to find similarities in values.

    If $runByColumns is TRUE then the comparison runs horizontally through the
    desired columns, others the comparison runs vertically.

    If no column is specified then the the operation runs over
    all columns.

    The resulting DataFrame is a matrix of values representing closeness of the
    ajoining values.
*/
public function correlation(string $method, array $columns = null, bool $runByColumns = true);

/*
    Produce a formatted string containing a summary of the DataFrame,
    including:
        - row count
        - standard deviation for each column
        - average for each column
        - minimum value for eachc column 
        - quantiles for 25%, 50% and 75%
        - maximum value for eachc column 

    If any of the columns have a display transformer attached, then
    they will be formatted accordingly prior to output.
*/
public function summary();

/*
    Produce a set of seperate DataFrames whereby all rows
    of the current DataFrame are split by the given column.

    The result is a GroupedDataFrame, containing all resulting
    DataFrames within.
*/
public function groupby(string $column);

/*
    Remove the specified columns from the DataFrame.

    If $inPlace is TRUE then this operation modifies the 
    current DataFrame, otherwise a copy is returned.
*/
public function drop_columns($columns, $inplace = false);

/*
    Remove the rows starting at $start and ending at $end from
    the DataFrame, where $start and $end represent the relevant
    row indexes.

    If $inPlace is TRUE then this operation modifies the 
    current DataFrame, otherwise a copy is returned.
*/
public function drop_rows($start, $end = null, $inplace = false);

/* 
    Find all duplicate values for a given set of columns, or
    every column if none are supplied.

    This method only compares corresponding values between rows
    of each column. That is, it the comparison is performed
    vertically, not horizontally.
*/
public function duplicated(...$columns);

/* 
    Drop all duplicates values within the given columns, or
    every column if none are supplied.

    If $inplace is TRUE then this operation is performed on 
    receiver, otherwise a modified copy is returned.

    See duplicated() for more information.
*/
public function drop_duplicates($inplace = false, ...$columns);

/*
    Generate a copy of the DataFrame with the columns
    and row indexes rotated to become the other.

    This has the effect of grouping common values under
    a singular index.

    If a set of columns are provided then all other 
    columns are stripped out of the result.
*/
public function pivot(...$columns);

/*
    The reverse operation of pivot(). Rotate the row
    indexes and columns back in the other direction.

    Note that $columns in this method actually refer
    to the current grouped indexes that you wish to
    revert back into actual columns. 

    If no columns are supplied then all indexes are 
    used.
*/
public function depivot(...$columns);

/*
    Perform a complex transformation on the DataFrame where
    by the column specified by $groupColumn becomes the index
    and all other columns are merged via the merge map.

    The $mergeMap is an associative array where by each column
    name specified as a key becomes a column in the resulting 
    DataFrame and each column name specified as a value in the 
    array becomes the corresponding value of that column.
*/
public function transpose(string $groupColumn, array $mergeMap);

/*
    Transform the value of one or more columns using the provided
    callback. If no columns are specified then the operation
    applies to all.

    Callback format: myFunc($value, $columnName, $rowIndex)
*/
public function transform($callback, ...$columns);

/*
    Add a new row to the DataFrame. $row is an associative
    array where the keys should correspond to one or more
    of the column headers in the DataFrame.

    ** Do not use new or unknown keys not already present
    in the DataFrame.
*/
public function add_row(array $row = [], $key = '');

/*
    Add a new column to the DataFrame using the provided
    callback to supply the data. The callback will be called
    for every row currently in the DataFrame.

    Callback format: myFunc($row, $rowIndex)
        - $row: associative array containing the value for each column.
*/
public function add_column(string $column, callable $callback);

/*
    Apply a transformation callback for one or more columns when
    outputing the DataFrame. If no columns are specified then the 
    operation applies to all. 

    You might use this to format timestamps into dates or to unify
    the display of currency.

    The callback should return the formatted value as it should be
    displayed.

    This method does not modify the original value within the Dataframe.

    Callback format: myFunc($value)
*/
public function apply_display_transformer($callback, ...$columns);

/*
    Produce a plot object (from the plotlib module) auto-configured
    to create an image-based graph of one or more columns.

    $options represent the chart configuation.
        - title:         Filename of the chart. Defaults to the chart type 
                         and series being plotted.
        - columns:         Array of the column names to produce charts for.
        - xcolumn:         A column name to use as the x-axis.
        - one:             If TRUE then all columns will be rendered onto one chart. 
                           When FALSE multiple charts are generated.
        - min:            The minimum Y-value to render.
        - max:            The maximum Y-value to render.
        - lines:        Array of infinite lines to be drawn onto the chart. Each 
                        item in the array is an associative array containing the 
                        the following options:
                        - direction: Either VERTICAL or HORIZONTAL.
                        - value: the numerical position on the respective axis that
                             the line will be rendered.
                        - color: a colour name (e.g. red, blue etc) for the line 
                             colour. Default is red.
                        - width: the stroke width of the line, default is 1.
        - labelangle:    Angular rotation of the x-axis labels, default is 0.    
        - bars:            A liniar array of values to represent an auxiliary/background
                        bar chart dataset. This will plot on it's own Y axis.
        - barColor:        The colour of the bars dataset, default is 'lightgray'.
        - barWidth:        The width of each bar in the bars dataset, default is 7.
                        
    $type represents the type of chart (e.g line, box, bar etc). Possible values:
            - line:         line chart.
            - linefill:     line chart with filled area.
            - bar:            bar chart.
            - barstacked:    bar chart with each series stacked atop for each
                            data point.
            - scatter:        scatter chart.
            - box:            Similar to a stock plot but with a fifth median value.

    See: plotlib for possibly more information.
*/
public function plot(string $type, array $options = []);

/*
    Produce a candle-stick chart, typically used for tracking stock prices.

    You must specify exactly 4 columns.

    $options can include a 'volume' key, specifying an associative array with
    the subkeys 'key', 'color' and 'width' for representing volume as a
    background bar chart.

    All other standard option keys can be passed in.
*/
public function stock(string $openP, string $closeP, string $lowP, string $highP, array $options = []);

/*
    Create a box plot chart, which is a singular data point of box-like
    appearance that illustrates the place of the 25%, 50% and 75% quantiles
    as well as the outer whiskers.
*/
public function box(...$columns);

/*
    Create a bar chart styled in the fashion of a histogram.
*/
public function hist(array $options = []);

/*
    Export the Dataframe to a delimetered text file (CSV).

    $filePath: The destination file.
    $columns: The columns to export, or all if null is supplied.
    $delimeter: The character that seperates each column.
*/
public function export(string $filePath, array $columns = null, string $delimeter = ',');
```


### GroupedDataFrame Methods

The GroupedDataFrame is a special class that manages a group of normal DataFrame objects. Normal actions on the DataFrame can be called and actioned against all objects within the set.

This class is used internally by DataFrame and you should not need to instanciate it yourself under most conditions.

Adheres to interfaces:
* _Stringable_
* _ArrayAccess_
* _Countable_
* _IteratorAggregate_

It also utilises the <code>__call</code> magic method to allow most of the DataFrame methods to be passed through to each frame in the set.

```php
/*
    Construct a new GroupedDataFrame containing multiple DataFrame objects.

    The $groupedColumn maintains a record of the singular DataFrame column
    that was used to split the original frame.
*/
public function __construct(array $groups, string $groupedColumn);

/* 
    Combine all frames within the group back into a singular DataFrame.
    
    If $keepIndexes is set to true then all existing indexes are kept and
    merged. Keep in mind that you may suffer data overwrite if one or more
    of the frames in the set have matching indexes.

    If $keepIndexes is set to false then the new DataFrame reindexes all rows
    with a standard numerical sequence starting from 0.

    Returns the new combined DataFrame.
*/
public function combine(bool $keepIndexes = true);
```



### PackedSequence Methods

A memory-efficient, variable-length array of fixed size elements. It is particularly useful for large numerical arrays or indexes.

 A PackedSequence has the following characteristics:

* Is sequentially indexed and non-associative.

* All elements within the array must be the same amount of bytes. NULL values are not accepted.

* Auto-packing and unpacking is available for values going in and out of the array.



Adheres to interfaces:

* _Stringable_
* _ArrayAccess_
* _Countable_
* _IteratorAggregate_



```php
/*
  $itemSize should be either a string code accepted by PHP's built-in
  pack() method, or an integer specifying the raw byte size if no
  packing is required.

  $startingValues is an optional array of starting numbers to add
  to the array.
*/
public function __construct($itemSize, ?array $startingValues = null)

// Return the total number of elements within the array.
public function count()

// Print all values to the output buffer.
public function print(string $prependMessage = '')

/* 
  Add a value to the end of the array. If the value is an array or a 
  traversable object then each element of it will instead be added.
*/
public function add(...$values)

// Insert a new item into the array at a given index anywhere up to the end of the array.
public function insert(int $index, $value)

/* 
  Overwrite an existing value with the one provided. If $index is greater than the current
  count then the value is appended to the end.
*/
public function set(int $index, $value)

// Return an item from the array at the given index.
public function get(int $index)

// Remove an item from the array  at the given index.
public function delete(int $index)

/* 
  Pop an item off the end of the array. If $poppedValue is provided 
  then it is filled with the value that was removed.
*/
public function pop(&$poppedValue = null)

/* 
  Shift an item off the start of the array. If $shiftedItem is provided 
  then it is filled with the value that was removed.
*/
public function shift(&$shiftedItem = null)

// Remove all elements from the array.
public function clear()

// Return a new vector containing all indexes.
public function keys()

// Returns TRUE if there are 0 elements in the array, FALSE otherwise.
public function empty()

// Return the first value in the array.
public function first()

// Return the last value in the array.
public function last()

/*
  Returns TRUE if any of the values within the array are equal to the value
  provided, FALSE otherwise.

  A callback may be provided as the match to perform more complex testing.

  Callback format: myFunc($value) -> bool

  For basic (non-callback) matches, setting $strict to TRUE will enforce 
  type-safe comparisons.
*/
public function any($match, bool $strict = false)

/*
  Returns TRUE if all of the values within the array are equal to the value
  provided, FALSE otherwise.

  A callback may be provided as the match to perform more complex testing.

  Callback format: myFunc($value) -> bool

  For basic (non-callback) matches, setting $strict to TRUE will enforce 
  type-safe comparisons.
*/
public function all($match, bool $strict = false)

/* 
  Search the array for the given needle (subject). This function is an
  alias of any().
*/
public function contains($needle)

// Determines if the array ends with the needle.
public function ends_with($needle)

// Determines if the array starts with the needle.
public function starts_with($needle)

/*
	Filter the contents of the array using the provided callback. 

  Callback format: myFunc($value, $index) -> bool
*/
public function filter(callable $callback)

/*
Apply a callback function to the array.

Callback format: myFunc($value, $index) -> mixed
*/
public function map(callable $callback)

/*
  Pad the array to the specified length with a value. If $count is positive then 
  the array is padded on the right, if it's negative then on the left. 
*/
public function pad(int $count, $value)

/* 
  Return a copy of the array only containing the number
  of rows from the start as specified by $count.
*/
public function head(int $count)

/* 
Return a copy of the array only containing the number
of rows from the end as specified by $count.
*/
public function tail(int $count)

/* 
Return a copy of the array only containing the the rows
starting from $start through to the given length.
*/
public function slice(int $start, ?int $length = null)

/*
  Return a copy of the array containing a random subset of the elements. The minimum and 
  maximum values can be supplied to focus the random sample to a more constrained subset. 
*/
public function sample(int $minimum, ?int $maximum = null)

/*
  Provide a maximum or minimum (or both) constraint for the values in the array.

  If a value exceeds that constraint then it will be set to the constraint.

  If either the lower or upper constraint is not needed then passing in null will 
  ignore it.
*/
public function clip($lower, $upper = null)

/*
	Swap the positions of 2 values within the array.
*/
public function swap(int $index1, int $index2)

/*
  Sort the array in either ASCENDING or DESCENDING direction.
*/
public function sort(bool $dir = ASCENDING)

// Reserve the order of the elements.
public function reverse()

/*
  Compute a sum of the values within the array.
*/
public function sum()

/*
  Compute the average of the values within the array.
*/
public function avg()

/*
  Return the maximum value present within the array.
*/
public function max()

/*
Return the minimum value present within the array.
*/
public function min()

/*
  Compute the product of the values within the array.
*/
public function product()


/*
  Compute the variance of values within the array.
*/
public function variance()

/*
  Round all values in the array up or down to the given decimal point precesion.
*/
public function round(int $precision, int $mode = PHP_ROUND_HALF_UP)

```



### PackedArray Methods

A memory-efficient, variable-length array of variable-sized elements.

A PackedArray has the following characteristics:

* Is sequentially indexed and non-associative.

* Elements within the array may vary in their byte length. NULL values are not accepted. Empty strings are internally stored as a 1-byte entry.

* Auto-packing and unpacking is available for values going in and out of the array.



Auto-Packing works as follows:

* Integers are either encoded as 32bit/4 byte or 64bit/8-byte sequences, depending on the hardware being used.
* Decimal numbers are always encoded as double precision 8-byte sequences.
* Strings are input directly.
* Objects and arrays are serialised.



This class should not be considered a blanket replacement for native arrays, instead the key is to identify when it is a better fit for any particular problem.

In general native arrays offer flexibility and speed over memory consumption, where as a packed array prioritises memory usage for less flexibility. PackedArrays are built to address situations where working with large data sets that challenge the available RAM on the running machine can not be practically solved by other means.

It is worth noting that if you have access to the [Ds Extension](https://github.com/php-ds/ext-ds), the native classes provided by it may prove to be a higher performant solution.

Adheres to interfaces:

* _Stringable_
* _ArrayAccess_
* _Countable_
* _IteratorAggregate_



```php
/*
   Create a new PackedArray, optionally pre-filling it with a series of
   items.
*/
public function __construct(array $startingArray = [])

/* 
  Print all values to the output buffer. Optionally pass in a 
  title/starting message to print out first.
*/
public function print(string $prependMessage = '')

// Return the number of elements within the array.
public function count()

/* 
  Add a value to the end of the array. If the value is an array or a 
  traversable object then it will be serialised prior to being stored.
*/
public function add(...$values)

// Insert a new item into the array at a given index anywhere up to the end of the array.
public function insert(int $index, $newVal)

/* 
  Overwrite an existing value with the one provided. If $index is greater than the current
  count then the value is appended to the end.
*/
public function set(int $index, $value)

// Return an item from the array at the given index.
public function get(int $index)

// Remove an item from the array  at the given index.
public function delete(int $index)

/* 
    Pop an item off the end of the array. If $poppedValue is provided 
    then it is filled with the value that was removed.
*/
public function pop(&$poppedValue = null)

/* 
    Shift an item off the start of the array. If $shiftedItem is provided 
    then it is filled with the value that was removed.
*/
public function shift(&$shiftedItem = null)

// Remove all elements from the array.
public function clear()

// Return a new vector containing all indexes.
public function keys()

// Returns TRUE if there are 0 elements in the array, FALSE otherwise.
public function empty()

// Return the first value in the array.
public function first()

// Return the last value in the array.
public function last()

/*
	Returns TRUE if any of the values within the array are equal to the value
	provided, FALSE otherwise.

	A callback may be provided as the match to perform more complex testing.

	Callback format: myFunc($value) -> bool

	For basic (non-callback) matches, setting $strict to TRUE will enforce 
	type-safe comparisons.
*/
public function any($match, bool $strict = false)

/*
	Returns TRUE if all of the values within the array are equal to the value
	provided, FALSE otherwise.

	A callback may be provided as the match to perform more complex testing.

	Callback format: myFunc($value) -> bool

	For basic (non-callback) matches, setting $strict to TRUE will enforce 
	type-safe comparisons.
*/
public function all($match, bool $strict = false)

/* 
	Search the array for the given needle (subject). This function is an
	alias of any().
*/
public function contains($needle)

// Determines if the array ends with the needle.
public function ends_with($needle)

// Determines if the array starts with the needle.
public function starts_with($needle)

/*
	Filter the contents of the array using the provided callback. 

    Callback format: myFunc($value, $index) -> bool
*/
public function filter(callable $callback)

/*
	Apply a callback function to the array.

	Callback format: myFunc($value, $index) -> mixed
*/
public function map(callable $callback)

/*
	Pad the array to the specified length with a value. If $count is positive then 
	the array is padded on the right, if it's negative then on the left. 
*/
public function pad(int $count, $value)

/* 
	Return a copy of the array only containing the number
	of rows from the start as specified by $count.
*/
public function head(int $count)

/* 
	Return a copy of the array only containing the number
	of rows from the end as specified by $count.
*/
public function tail(int $count)

/* 
	Return a copy of the array only containing the the rows
	starting from $start through to the given length.
*/
public function slice(int $start, ?int $length = null)

/*
	Return a copy of the array containing a random subset of the elements. The minimum and 
	maximum values can be supplied to focus the random sample to a more constrained subset. 
*/
public function sample(int $minimum, ?int $maximum = null)

/*
	Provide a maximum or minimum (or both) constraint for the values in the array.

	If a value exceeds that constraint then it will be set to the constraint.

	If either the lower or upper constraint is not needed then passing in null will 
	ignore it.
*/
public function clip($lower, $upper = null)

/*
  Swap the positions of 2 values within the array.
*/
public function swap(int $index1, int $index2)

/*
	Sort the array in either ASCENDING or DESCENDING direction.
    
  If $key is provided then the operation will be performed on
  the corresponding sub value of array element, assuming each
  element is an array or an object that provides array access.
*/
public function sort(bool $dir = ASCENDING, ?string $key = null)

// Reserve the order of the elements.
public function reverse()

/*
	Compute a sum of the values within the array.

  If $key is provided then the operation will be performed on
  the corresponding sub value of array element, assuming each
  element is an array or an object that provides array access.
*/
public function sum($key = null)

/*
	Compute the average of the values within the array.

  If $key is provided then the operation will be performed on
  the corresponding sub value of array element, assuming each
  element is an array or an object that provides array access.
*/
public function avg($key = null)

/*
	Return the maximum value present within the array.

  If $key is provided then the operation will be performed on
  the corresponding sub value of array element, assuming each
  element is an array or an object that provides array access.
*/
public function max($key = null)

/*
	Return the minimum value present within the array.

  If $key is provided then the operation will be performed on
  the corresponding sub value of array element, assuming each
  element is an array or an object that provides array access.
*/
public function min($key = null)

/*
	Compute the product of the values within the array.

  If $key is provided then the operation will be performed on
  the corresponding sub value of array element, assuming each
  element is an array or an object that provides array access.
*/
public function product($key = null)

/*
	Compute the variance of values within the array.

  If $key is provided then the operation will be performed on
  the corresponding sub value of array element, assuming each
  element is an array or an object that provides array access.
*/
public function variance($key = null)

/*
	Round all values in the array up or down to the given decimal point precesion.
*/
public function round(int $precision, int $mode = PHP_ROUND_HALF_UP)
```



## Credits

Theo Howell

**NOTE:** Portions of the mathematical methods are borrowed from various freely available open source projects and code snippets. Appropriate credit and links are given where applicable in the relevant sections of the code.

## License

The MIT License (MIT). Please see [License File](license.txt) for more information.