###### PHEXT > [DataKit](../README.md) > [API Reference](index.md) > math
------
### math
A broad collection of general mathematical functions. This class acts as a support class of statistical calculations for the DataFrame and Vector classes.

Many of these methods are ported from open source code, freely available on the internet. Credits and links are listed where applicable.
#### Methods
- [standard_deviation](#standard_deviation)
- [variance](#variance)
- [avg](#avg)
- [min](#min)
- [max](#max)
- [median](#median)
- [quantile](#quantile)
- [normalise](#normalise)
- [correlation_pearson](#correlation_pearson)
- [cumulative_min](#cumulative_min)
- [cumulative_max](#cumulative_max)
- [cumulative_sum](#cumulative_sum)
- [cumulative_prod](#cumulative_prod)
- [correlation_spearman](#correlation_spearman)
- [coefficient](#coefficient)
- [distances](#distances)
- [nf_round](#nf_round)

------
##### standard_deviation
```php
static public function standard_deviation(array $a, bool $sample = false) : float|false
```
Compute the standard deviation of the values in an array.

This method was originally provided by user levim@php.dot.net, in the comments for the corresponding method of the stats PHP extension. see: https://www.php.net/manual/en/function.stats-standard-deviation.php#114473

Original comment:

> This user-land implementation follows the implementation quite strictly;
it does not attempt to improve the code or algorithm in any way. It will
raise a warning if you have fewer than 2 values in your array, just like
the extension does (although as an E_USER_WARNING, not E_WARNING).
>

- **list<int|float>** $a
- **bool** $sample [optional] Defaults to false

**Returns:**  float|false The standard deviation or `FALSE` on error.


------
##### variance
```php
static public function variance(array $array) : float
```
Compute the variance of an array of values.

- **list<int|float>** $array The input array of values.


------
##### avg
```php
static public function avg(array $array) : int|float
```
Produce the average of an array of numbers.

- **list<int|float>** $array The input array of values.


------
##### min
```php
static public function min(array $array) : ?float
```
Find the minimum floating point number present in an array. This method works correctly when comparing negative floating point units.

- **list<int|float>** $array The input array of values.

**Returns:**  ?float The lowest value in the array or `NULL` if the array is empty.


------
##### max
```php
static public function max(array $array) : ?float
```
Find the maximum floating point number present in an array. This method works correctly when comparing negative floating point units.

- **list<int|float>** $array The input array of values.

**Returns:**  ?float The highest value in the array or `NULL` if the array is empty.


------
##### median
```php
static public function median(array $array) : int|float|bool
```
Return the middle number within an array.

- **list<mixed>** $array The input array of values.


------
##### quantile
```php
static public function quantile(array $array, float $quantile) : int|float|bool
```
Compute the quantile from the given percentile of the given array.

- **list<int|float>** $array The input array of values.
- **float** $quantile The quantile to compute.


------
##### normalise
```php
static public function normalise(array $array) : array
```
Normalise a series of numbers to a range between 0 and 1.

- **list<int|float>** $array The input array of values.

**Returns:**  list<int|float> A copy of the input array with all values normalised.


------
##### correlation_pearson
```php
static public function correlation_pearson(array $x, array $y) : int|float
```
Compute a correlation using the Pearson method with the two given arrays.

- **list<int|float>** $x The first input array of values.
- **list<int|float>** $y The second input array of values.

**Returns:**  float|int 1.0 if both arrays are empty, -1.0 if both arrays do not match in size, otherwise a float or int representing the result of the correlation.

This method was taken from: https://gist.github.com/fdcore/a4dd72580244ffeac3039741b4904b31


------
##### cumulative_min
```php
static public function cumulative_min(array $array) : array
```
Accumulative minimum of the values within an array.

- **list<int|float|null>** $array The input array of values.

**Returns:**  list<int|float|null> An array of running minimum values.


------
##### cumulative_max
```php
static public function cumulative_max(array $array) : array
```
Accumulative maximum of the values within an array.

- **list<int|float|null>** $array The input array of values.

**Returns:**  list<int|float|null> An array of running maximum values.


------
##### cumulative_sum
```php
static public function cumulative_sum(array $array) : array
```
Accumulative sum of the values within an array.

- **list<int|float|null>** $array The input array of values.

**Returns:**  list<int|float|null> An array of running totals.


------
##### cumulative_prod
```php
static public function cumulative_prod(array $array) : array
```
Accumulative product of the values within an array.

- **list<int|float|null>** $array The input array of values.

**Returns:**  list<int|float|null> An array of running products.


------
##### correlation_spearman
```php
static public function correlation_spearman(array $data1, array $data2) : int|float|bool
```
Compute a correlation using the Spearman method with the two given arrays.

- **list<int|float>** $data1 The first input array of values.
- **list<int|float>** $data2 The second input array of values.

**Returns:**  float|int|bool 1 if both arrays are empty, `FALSE` if both arrays are not the same size, otherwise a float or int representing the result of the correlation.

This method is part of the spearman correlation and was originally written by Alejandro Mitrou under the GPL license.


**See:** : http://www.wisetonic.com/ 
**See:** : https://github.com/amitrou/Spearman-Correlation


------
##### coefficient
```php
static public function coefficient(array $distances) : int|float|bool
```
Compute the coefficient of an array of distances.

- **list<int|float>** $distances The input array of values.

**Returns:**  float|int|bool The calculated coefficient.

This method is part of the spearman correlation and was originally written by Alejandro Mitrou under the GPL license.


**See:** : http://www.wisetonic.com/ 
**See:** : https://github.com/amitrou/Spearman-Correlation


------
##### distances
```php
static public function distances(array $ranking1, array $ranking2) : array
```
Return an array of distances computed from the values of the two given arrays.

- **list<int|float>** $ranking1 The first input array of values.
- **list<int|float>** $ranking2 The second input array of values.

**Returns:**  list<int|float> An array of computed distances.

This method is part of the spearman correlation and was originally written by Alejandro Mitrou under the GPL license.


**See:** : http://www.wisetonic.com/ 
**See:** : https://github.com/amitrou/Spearman-Correlation


------
##### nf_round
```php
static public function nf_round(array|string|int|float $value, int $precision, int $mode = PHP_ROUND_HALF_UP) : array|string
```
Round the given value(s) to the desired precision. This method performs a double round, first rounding the float using the preferred mode of rounding, then converting the result to a string with number_format in order to hold the precision.

- **float|int|string|list<int|float|string>** $value The value to be rounded. If an array is passed in then each element within is rounded.
- **int** $precision The number of decimal digits to round to.
- **int-mask<1,2,3,4>** $mode<1|2|3|4> The rounding mode used.

**Returns:**  string|list<string> The rounded value, or array of rounded values (depending on the input).


**See:**  Both [round()](https://www.php.net/manual/en/function.round.php) and [number_format()](https://www.php.net/manual/en/function.number-format.php) for further information on rounding modes and how the rounding engine works.


------
