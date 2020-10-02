###### PHEXT > [DataKit](../README.md) > [API Reference](index.md) > math
------
### math
A broad collection of general mathematical functions. This class acts as a support class of statistical calculations for the DataFrame and Vector classes.

Many of these methods are ported from open source code, freely available on the internet. Credits and links are listed where applicable.
#### Methods
[standard_deviation](#standard_deviation)
[variance](#variance)
[avg](#avg)
[min](#min)
[max](#max)
[median](#median)
[quantile](#quantile)
[normalise](#normalise)
[correlation_pearson](#correlation_pearson)
[cumulative_min](#cumulative_min)
[cumulative_max](#cumulative_max)
[cumulative_sum](#cumulative_sum)
[cumulative_prod](#cumulative_prod)
[correlation_spearman](#correlation_spearman)
[coefficient](#coefficient)
[distances](#distances)

------
##### standard_deviation
```php
static public function standard_deviation(array $a, $sample = false) 
```
Compute the standard deviation of the values in an array.

This method was originally provided by user levim@php.dot.net, in the comments for the corresponding method of the stats PHP extension. see: https://www.php.net/manual/en/function.stats-standard-deviation.php#114473

Original comment:

> This user-land implementation follows the implementation quite strictly;
it does not attempt to improve the code or algorithm in any way. It will
raise a warning if you have fewer than 2 values in your array, just like
the extension does (although as an E_USER_WARNING, not E_WARNING).
>

- **array** $a
- **bool** $sample [optional] Defaults to false

**Returns:**  float|bool The standard deviation or `FALSE` on error.


------
##### variance
```php
static public function variance(array $arr) 
```
Compute the variance of an array of values.


------
##### avg
```php
static public function avg(array $array) 
```
Produce the average of an array of numbers.


------
##### min
```php
static public function min(array $array) 
```
Find the minimum floating point number present in an array. This method works correctly when comparing negative floating point units.

Returns the lowest value in the array or null if the array is empty.


------
##### max
```php
static public function max(array $array) 
```
Find the maximum floating point number present in an array. This method works correctly when comparing negative floating point units.

Returns the highest value in the array or null if the array is empty.


------
##### median
```php
static public function median(array $arr) 
```
Return the middle number within an array.


------
##### quantile
```php
static public function quantile(array $array, $quantile) 
```
Compute the quantile from the given percentile of the given array.


------
##### normalise
```php
static public function normalise(array $array) 
```
Normalise a series of numbers to a range between 0 and 1.


------
##### correlation_pearson
```php
static public function correlation_pearson(array $x, array $y) 
```
Compute a correlation using the Pearson method with the two given arrays.

This method was taken from: https://gist.github.com/fdcore/a4dd72580244ffeac3039741b4904b31


------
##### cumulative_min
```php
static public function cumulative_min(array $array) 
```
Accumulative minimum of the values within an array.


------
##### cumulative_max
```php
static public function cumulative_max(array $array) 
```
Accumulative maximum of the values within an array.


------
##### cumulative_sum
```php
static public function cumulative_sum(array $array) 
```
Accumulative sum of the values within an array.


------
##### cumulative_prod
```php
static public function cumulative_prod(array $array) 
```
Accumulative product of the values within an array.


------
##### correlation_spearman
```php
static public function correlation_spearman(array $data1, array $data2) 
```
Compute a correlation using the Spearman method with the two given arrays.

This method are part of the spearman correlation and were originally written by Alejandro Mitrou under the GPL license.

see: http://www.wisetonic.com/ see: https://github.com/amitrou/Spearman-Correlation


------
##### coefficient
```php
static public function coefficient(array $distances) 
```
Compute the coefficient of an array of distances.

This method are part of the spearman correlation and were originally written by Alejandro Mitrou under the GPL license.

see: http://www.wisetonic.com/ see: https://github.com/amitrou/Spearman-Correlation


------
##### distances
```php
static public function distances(array $ranking1, array $ranking2) 
```
Return an array of distances computed from the values of the two given arrays.

This method are part of the spearman correlation and were originally written by Alejandro Mitrou under the GPL license.

see: http://www.wisetonic.com/ see: https://github.com/amitrou/Spearman-Correlation


------
