###### PHEXT > [DataKit](../../README.md) > API Reference

------

## API Reference



[Importer](Importer.md)

The Importer class allows easy importing of both small and large CSV files.

[SMA](SMA.md)

The SMA class is used to compute Simple Moving Averages. It is used by alternating between adding new values to the array and calculating the current average.

[EMA](EMA.md)

The EMA class is used to compute Exponential Moving Averages. It is used by alternating between adding new values to the array and calculating the current average.

[DOMScraper](DOMScraper.md)

The DOMScraper is a barebones web scraper that works by quickly traversing a series of nested elements in a DOMDocument and delivering the final set of elements to a callback for processing.

[Vector](Vector.md)

Vector is an object orientated wrapper for native PHP arrays. It exposes most of the native built-in methods as well as adding additional functions useful for both basic statistical calculations and bulk operations.

[DataFrame](DataFrame.md)

The DataFrame is a class inspired by, and loosely based off of, a class by the same name from the Pandas library in Python. It specialises in working with 2 dimensional arrays (rows and columns) that may originate from data sources such as CSV files or data fetched from a relational database.

[GroupedDataFrame](GroupedDataFrame.md)

The GroupedDataFrame is a special class that manages a group of normal DataFrame objects. Normal actions on the DataFrame can be called and actioned against all objects within the set.

[PackedSequence](PackedSequence.md)

[PackedArray](PackedArray.md)

Both PackedSequence and PackedArray are array structures designed for working in tight memory situations. A full description is available further down in the method reference.

- Use a [PackSequence](PackedSequence.md) for working with a uniform set of elements of the same type and byte size (e.g. all Ints or all floats).
- Use a [PackedArray](PackedArray.md) when your dataset has elements that vary in size and/or type.

Both classes have almost identical methods and the examples below can easily be translated between the the two.

[CSV](CSV.md)

The CSV class can be used for producing CSV documents. It abstracts the mechanics of producing the file format, allowing your code to focus on its own logic.

[math](math.md)

A broad collection of general mathematical functions. This class acts as a support class of statistical calculations for the DataFrame and Vector classes.

------

### Global Methods

##### vector

```php
function vector(...$items)
```

Create a new Vector.

This method takes a variable set of parameters, with each being added as a seperate element within the array.

If only one element is passed in and it is an array then the array will be transformed into the vector.



------

##### dataframe

```php
function dataframe(?array $data = null, array $headers = null)
```

Create a new DataFrame with the supplied rows & columns. 

**See** [DataFrame::__construct](DataFrame.md#__construct) for a proper description.



------

### Constants

```php
define('ASCENDING', true);
define('DESCENDING', false);
define('OOB_ALL', 2);
define('OOB_UPPER', 1);
define('OOB_LOWER', 0);
define('LAST_ROW', '__LASTROW__');
define('FIRST_ROW', '__FIRSTROW__');
```

