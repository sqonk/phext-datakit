# PHEXT Datakit

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.3-8892BF.svg)](https://php.net/)
[![License](https://sqonk.com/opensource/license.svg)](license.txt)
[![Build Status](https://travis-ci.org/sqonk/phext-datakit.svg?branch=master)](https://travis-ci.org/sqonk/phext-datakit)

Datakit is a library that assists with data analysis and research. It includes classes for working with tables of data and deriving statistical information, importing those tables from file formats such as CSV, a class wrapper with statistical methods for PHP arrays, as well as memory efficient packed arrays.

It also contains a small set of stand-alone functions and defined constants that import across the global namespace.

You can also combine it with [Visualise](https://github.com/sqonk/phext-visualise) to hook up real-time visual output when working from the command line. 


## About PHEXT

The PHEXT package is a set of libraries for PHP that aim to solve common problems with a syntax that helps to keep your code both concise and readable.

PHEXT aims to not only be useful on the web SAPI but to also provide a productivity boost to command line scripts, whether they be for automation, data analysis or general research.

## Install

Via Composer

``` bash
$ composer require sqonk/phext-datakit
```



API Reference
------------

Please see the [API Reference](docs/api/index.md) for full documentation on each class and the available methods.



Datakit Features by Example
----------------

* [Importer](#importer) for working with CSV, delimited files and other data sources.
	- [CSV Files](#importer---csv---files)
	- [CSV In Memory](#importer---csv---data)
	- [CSV Automated Processing](#importer---csv---data)
	- [Generic Delimitered import](#importer---generic)
* [SMA](#sma) Simple Moving Average calculator
* [EMA](#ema): Exponential Moving Average calculator
* [DOMScraper](#domscaper): A light weight and unsophisticated web scraper
* [CSV](#csv): A class for producing CSV documents.
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
		* [Normalise](#vector---data-manipulation---normalise)
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
		* [Clipping and pruning](#dataframe---data-manipulation---clipping)
		* [Normalise](#dataframe---Data-Manipulation---Normalise)
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
	- [Filtering and Sorting](#dataframe---filtering-and-sorting)
		* [Sorting](#dataframe---filtering-and-sorting---sorting)
		* [Filtering](#dataframe---filtering-and-sorting---filtering)
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

Or add multiple values then run through the acquired averages:

```php
// create a moving average of 3 items, with a default rounding precision to 
// 2 decimal places.
$sma = new SMA(maxItems:3, defaultPrecision:2);

// add a series of numbers to the moving average as the input.
$sma->add(1, 0.43, 3, 4.33, 5, 6, 8);

// print all calculated averages, rounded to 3 decimal places.
println($sma->all(precision:3));
/*
array (
  0 => 1.0,
  1 => 0.715,
  2 => 1.477,
  3 => 2.587,
  4 => 4.11,
  5 => 5.11,
  6 => 6.333,
)
*/

// The calculated averages can also be looped through..
foreach ($sma as $i => $avg)
  println("Average at position $i: $avg");
/*
Average at position 0: 1
Average at position 1: 0.72
Average at position 2: 1.48
Average at position 3: 2.59
Average at position 4: 4.11
Average at position 5: 5.11
Average at position 6: 6.33
*/
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

Or add multiple values then run through the acquired averages:

```php
// create a moving average of 3 items, with a default rounding precision to 
// 2 decimal places.
$ema = new EMA(maxItems:3, defaultPrecision:2);

// add a series of numbers to the moving average as the input.
$ema->add(1, 0.43, 3, 4.33, 5, 6, 8);

// print all calculated averages, rounded to 3 decimal places.
println($ema->all(precision:3));
/*
array (
  0 => 1.0,
  1 => 0.715,
  2 => 1.858,
  3 => 3.094,
  4 => 4.047,
  5 => 5.023,
  6 => 6.512,
)
*/

// The calculated averages can also be looped through..
foreach ($ema as $i => $avg)
  println("Average at position $i: $avg");
/*
Average at position 0: 1
Average at position 1: 0.72
Average at position 2: 1.86
Average at position 3: 3.09
Average at position 4: 4.05
Average at position 5: 5.02
Average at position 6: 6.51
*/
```



### DOMScaper

The DOMScraper is a barebones web scraper that works by quickly traversing a series of nested elements in a DOMDocument and delivering the final set of elements to a callback for processing.

``` php
use sqonk\phext\datakit\DOMScraper;

// Load the example HTML file into memory and pass it to the scraper.
$scraper = new DOMScraper(file_get_contents('docs/people.html'));

$result = $scraper->traverse([
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

.. or using a Generator:

```php
use sqonk\phext\datakit\DOMScraper;

// Load the example HTML file into memory and pass it to the scraper.
$scraper = new DOMScraper(file_get_contents('docs/people.html'));

$chain = [
  ['type' => 'id', 'name' => 'pageData'],
  ['type' => 'tag', 'name' => 'table', 'item' => 1],
  ['type' => 'tag', 'name' => 'tbody'],
  ['type' => 'tag', 'name' => 'tr']
];
foreach ($scraper->yield($chain, $result) as $tr)
{
  $tds = $tr->getElementsByTagName('td');
	
  $firstName = $tds[0]->textContent;
  $lastName = $tds[1]->textContent;
  $role = $tds[2]->textContent;
  $hours = $tds[3]->textContent;
  $days = $tds[4]->textContent;
	
  println("Name: $firstName $lastName", "Role: $role", "Works: $days ($hours)");  
}

```



### CSV

The CSV class can be used for producing CSV documents. It abstracts the mechanics of producing the file format, allowing your code to focus on its own logic.

```php
$csv = new CSV;

# set a mapping between the desired column headers and the array keys.
$csv->set_map(['Name' => 'a', 'Age' => 'b', 'Number' => 'c']);

# add a row using an associative array.
$csv->add_record(['a' => 'Doug', 'b' => 32, 'c' => 20]);

# add another row, this time with an object that adheres to ArrayAccess.
$csv->add_record(vector(['a' => 'Jane', 'b' => 25, 'c' => 21]));

println($csv);
/*
Will print: 

Name,Age,Number
Doug,32,20
Jane,25,21
*/

# Add a set of rows all at once.
$more_rows = [
  ['a' => 'Cameron', 'b' => 16, 'c' => 16],
  ['a' => 'Kim', 'b' => 28, 'c' => 19],
  ['a' => 'Jim', 'b' => 32, 'c' => 20],
  ['a' => 'Amanda', 'b' => 35, 'c' => 33]
];
$csv->add_records($more_rows);
                     
println($csv);
/*
Will print: 

Name,Age,Number
Doug,32,20
Jane,25,21
Cameron,16,16
Kim,28,19
Jim,32,20
Amanda,35,33
*/                     
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
$data->set(0, 'Green Grape');

// Print out what we have.
println($data);
/*
array (
  0 => 'Green Grape',
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

##### Vector - Data Manipulation - Normalise

```php
$data = vector(0, 5, 10, 15, 20);
println($data->normalise());
/*
array (
  0 => 0,
  1 => 0.25,
  2 => 0.5,
  3 => 0.75,
  4 => 1,
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

The DataFrame specialises in working with 2 dimensional arrays (rows and columns) that may originate from data sources such as CSV files or data fetched from a relational database.

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

##### Dataframe - Data Manipulation - Normalise

Transform all of the values in one or more columns to a value between 0 and 1.

```php
$df = dataframe([
    ['t1' => 0, 't2' => 5, 't3' => 11.69],
    ['t1' => 5, 't2' => 10, 't3' => 22.78],
    ['t1' => 10, 't2' => 15, 't3' => 3.65],
    ['t1' => 15, 't2' => 20],
    ['t1' => 20, 't2' => 25]
]);
println($df->normalise('t1', 't2', 't3')->round(2));
/*
     	  t1	  t2	  t3
_____	____	____	____
0    	   0	   0	0.42
1    	0.25	0.25	   1
2    	 0.5	 0.5	   0
3    	0.75	0.75	    
4    	   1	   1
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
5    	sepal-length	     	  5.4
8    	sepal-length	  4.4	     
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

Configuration Options

*******

The `hist` method takes an associative array of configuration options as follows:

- columns: array of column names to use (1 or more)
  
- bins: number of bins to use for the histogram. Defaults to 10.
  
- cumulative: create a stacked histogram showing the accumulative scale 
        along with the main. Defaults to FALSE
    
- title: displayed title of the histogram
  
- low: low range bins filter. Defaults to NULL.
  
- high: high range bins filter. Defaults to NULL.
        
        

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

![Simple Line Plot](https://sqonk.com/opensource/phext/datakit/docs/images/sepal-length_sepal-width_petal-length_petal-width.png)Configuration Options

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



## Credits

Theo Howell

**NOTE:** Portions of the mathematical methods are borrowed from various freely available open source projects and code snippets. Appropriate credit and links are given where applicable in the relevant sections of the code.



## License

The MIT License (MIT). Please see [License File](license.txt) for more information.