<?php
namespace sqonk\phext\datakit;

/**
*
* Data Kit
*
* @package		phext
* @subpackage	datakit
* @version		1
*
* @license		MIT see license.txt where applicable.
* @copyright	2019 Sqonk Pty Ltd whe where applicable.
*
* #### NOTE: If credits and are links provided for an individual method (or set) then
* please see corresponding license for external source as it may still be in effect
* regardless of whether it is included in this package.
*
*
* This file is distributed
* on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
* express or implied. See the License for the specific language governing
* permissions and limitations under the License.
*/

use sqonk\phext\core\arrays;
use sqonk\phext\core\numbers;

/**
 * A broad collection of general mathematical functions. This class acts as a support
 * class of statistical calculations for the DataFrame and Vector classes.
 *
 * Many of these methods are ported from open source code, freely available
 * on the internet. Credits and links are listed where applicable.
 */
class math
{
  /**
   * Compute the standard deviation of the values in an array.
   *
   * This method was originally provided by user levim@php.dot.net, in the comments
   * for the corresponding method of the stats PHP extension.
   * see: https://www.php.net/manual/en/function.stats-standard-deviation.php#114473
   *
   * Original comment:
   *
   * > This user-land implementation follows the implementation quite strictly;
   * it does not attempt to improve the code or algorithm in any way. It will
   * raise a warning if you have fewer than 2 values in your array, just like
   * the extension does (although as an E_USER_WARNING, not E_WARNING).
   * >
   *
   * -- parameters:
   * @param list<int|float> $a
   * @param bool $sample [optional] Defaults to false
   *
   * @return float|false The standard deviation or FALSE on error.
   */
  public static function standard_deviation(array $a, bool $sample = false): float|false
  {
    $n = count($a);
    if ($n === 0) {
      trigger_error("The array has zero elements.", E_USER_WARNING);
      return false;
    }
    if ($sample && $n === 1) {
      trigger_error("The array has only 1 element.", E_USER_WARNING);
      return false;
    }
    $mean = array_sum($a) / $n;
    $carry = 0.0;
    foreach ($a as $val) {
      $d = ((double) $val) - $mean;
      $carry += $d * $d;
    };
    if ($sample) {
      --$n;
    }
    return sqrt($carry / $n);
  }
    
  /**
   * Compute the variance of an array of values.
   *
   * -- parameters:
   * @param list<int|float> $array The input array of values.
   */
  public static function variance(array $array): float
  {
    if (count($array) == 0) {
      return 0.0;
    }
        
    $variance = 0.0;
    $average = self::avg($array);

    foreach ($array as $i) {
      // sum of squares of differences between
      // all numbers and means.
      if (is_numeric($i)) {
        $variance += pow(($i - $average), 2);
      }
    }

    return $variance / count($array);
  }

  /**
   * Produce the average of an array of numbers.
   *
   * -- parameters:
   * @param list<int|float> $array The input array of values.
   */
  public static function avg(array $array): float|int
  {
    $count = count($array);
    return $count ? array_sum($array) / $count : 0;
  }
    
  /**
   * Find the minimum floating point number present in an array. This method
   * works correctly when comparing negative floating point units.
   *
   * -- parameters:
   * @param list<int|float> $array The input array of values.
   *
   * @return ?float The lowest value in the array or NULL if the array is empty.
   */
  public static function min(array $array): ?float
  {
    $current = null;
    foreach ($array as $value) {
      if ($current === null or $current > (float)$value) {
        $current = (float)$value;
      }
    }
        
    return $current;
  }
    
  /**
   * Find the maximum floating point number present in an array. This method
   * works correctly when comparing negative floating point units.
   *
   * -- parameters:
   * @param list<int|float> $array The input array of values.
   *
   * @return ?float The highest value in the array or NULL if the array is empty.
   */
  public static function max(array $array): ?float
  {
    $current = null;
    foreach ($array as $value) {
      if ($current === null or $current < (float)$value) {
        $current = (float)$value;
      }
    }
        
    return $current;
  }
    
  /**
   * Return the middle number within an array.
   *
   * -- parameters:
   * @param list<mixed> $array The input array of values.
   */
  public static function median(array $array): float|int|bool
  {
    $count = count($array); // total numbers in array
    if ($count < 1) {
      trigger_error("The array has zero elements", E_USER_WARNING);
      return false;
    }
    sort($array, SORT_NUMERIC);
    $middleval = floor(($count-1) / 2); // find the middle value, or the lowest middle value
    if ($count % 2) {
      // odd number, middle is the median
      $median = $array[$middleval];
    } else {
      // even number, calculate avg of 2 medians
      $low = $array[$middleval];
      $high = $array[$middleval+1];
      if (! is_numeric($low) || ! is_numeric($high)) {
        return 0;
      }
      $median = (($low+$high)/2);
    }
    return $median;
  }

  /**
   * Compute the quantile from the given percentile of the given array.
   *
   * -- parameters:
   * @param list<int|float> $array The input array of values.
   * @param float $quantile The quantile to compute.
   */
  public static function quantile(array $array, float $quantile): float|int|bool
  {
    if (numbers::is_within(0, 1, $quantile)) {
      throw new \InvalidArgumentException("The quantile must be a decimal value between 0 and 1. [$quantile] was provided.");
    }
        
    if (count($array) < 1) {
      trigger_error("The array has zero elements.", E_USER_WARNING);
      return false;
    }
        
    sort($array);
    $pos = (count($array) - 1) * $quantile;

    $base = floor($pos);
    $rest = $pos - $base;

    if (isset($array[$base+1])) {
      if (! is_numeric($array[$base+1]) || ! is_numeric($array[$base])) {
        return 0;
      }
      return $array[$base] + $rest * ($array[$base+1] - $array[$base]);
    } else {
      if (! isset($array[$base])) {
        return 0;
      }
      return is_numeric($array[$base]) ? $array[$base] : 0;
    }
  }
    
  /**
   * Normalise a series of numbers to a range between 0 and 1.
   *
   * -- parameters:
   * @param list<int|float> $array The input array of values.
   *
   * @return list<int|float> A copy of the input array with all values normalised.
   */
  public static function normalise(array $array): array
  {
    $length = count($array);
    if ($length < 1) {
      throw new \LengthException("The array has zero elements");
    }
        
    $f = array_filter($array, function ($v) {
      return $v < 0 and is_float($v);
    });
    $negfloats = count($f) > 0;
        
    $min = $negfloats ? self::min($array) : min($array);
    $max = $negfloats ? self::max($array) : max($array);
                
    $out = [];
    foreach ($array as $i => $value) {
      $out[] = ($value - $min) / ($max - $min);
    }
        
    return $out;
  }
    
  /**
   * Compute a correlation using the Pearson method with the two given arrays.
   *
   * -- parameters:
   * @param list<int|float> $x The first input array of values.
   * @param list<int|float> $y The second input array of values.
   *
   * @return float|int 1.0 if both arrays are empty, -1.0 if both arrays do not match in size, otherwise a float or int representing the result of the correlation.
   *
   * This method was taken from: https://gist.github.com/fdcore/a4dd72580244ffeac3039741b4904b31
   */
  public static function correlation_pearson(array $x, array $y): float|int
  {
    if (count($x) == 0 && count($y) == 0) {
      return 1.0;
    }
        
    if (count($x) !== count($y)) {
      return -1.0;
    }
        
    $x = array_values($x);
    $y = array_values($y);
           
    $xs = array_sum($x) / count($x);
    $ys = array_sum($y) / count($y);
    $a = $bx = $by = 0;
        
    foreach (sequence(0, count($x)-1) as $i) {
      $xr = $x[$i] - $xs;
      $yr = $y[$i] - $ys;
      $a += $xr * $yr;
      $bx += pow($xr, 2);
      $by += pow($yr, 2);
    }
        
    $b = sqrt($bx*$by);
        
    return $a / $b;
  }

  /**
   * Accumulative minimum of the values within an array.
   *
   * -- parameters:
   * @param list<int|float|null> $array The input array of values.
   *
   * @return list<int|float|null> An array of running minimum values.
   */
  public static function cumulative_min(array $array): array
  {
    $out = [];
    foreach ($array as $value) {
      $out[] = ($value !== null) ? min(array_merge([$value], $out)) : null;
    }
    return $out;
  }

  /**
   * Accumulative maximum of the values within an array.
   *
   * -- parameters:
   * @param list<int|float|null> $array The input array of values.
   *
   * @return list<int|float|null> An array of running maximum values.
   */
  public static function cumulative_max(array $array): array
  {
    $out = [];
    foreach ($array as $value) {
      $out[] = ($value !== null) ? max(array_merge([$value], $out)) : null;
    }
    return $out;
  }
    
  /**
   * Accumulative sum of the values within an array.
   *
   * -- parameters:
   * @param list<int|float|null> $array The input array of values.
   *
   * @return list<int|float|null> An array of running totals.
   */
  public static function cumulative_sum(array $array): array
  {
    $out = [];
        
    $filtered = [];
    $nulls = [];
    $i = 0;
    foreach ($array as $key => $value) {
      if ($value !== null) {
        $filtered[$key] = $value;
      } else {
        $nulls[] = $i;
      }
      $i++;
    }
        
    $cnt = count($filtered);
    for ($i = 0; $i < $cnt; $i++) {
      if (arrays::contains($nulls, $i)) {
        $out[] = null;
      }
      $slice = ($i < $cnt-1) ? array_slice($filtered, 0, $i+1) : $filtered;
      $out[] = array_sum($slice);
    }
    return $out;
  }
    
  /**
   * Accumulative product of the values within an array.
   *
   * -- parameters:
   * @param list<int|float|null> $array The input array of values.
   *
   * @return list<int|float|null> An array of running products.
   */
  public static function cumulative_prod(array $array): array
  {
    $out = [];
        
    $filtered = [];
    $nulls = [];
    $i = 0;
    foreach ($array as $key => $value) {
      if ($value !== null) {
        $filtered[$key] = $value;
      } else {
        $nulls[] = $i;
      }
      $i++;
    }
        
    $cnt = count($filtered);
    for ($i = 0; $i < $cnt; $i++) {
      if (arrays::contains($nulls, $i)) {
        $out[] = null;
      }
      $slice = ($i < $cnt-1) ? array_slice($filtered, 0, $i+1) : $filtered;
      $out[] = array_product($slice);
    }
        
    return $out;
  }
    
  /**
   * Compute a correlation using the Spearman method with the two given arrays.
   *
   * -- parameters:
   * @param list<int|float> $data1 The first input array of values.
   * @param list<int|float> $data2 The second input array of values.
   *
   * @return float|int|bool 1 if both arrays are empty, FALSE if both arrays are not the same size, otherwise a float or int representing the result of the correlation.
   *
   * This method is part of the spearman correlation and was originally
   * written by Alejandro Mitrou under the GPL license.
   *
   * @see: http://www.wisetonic.com/
   * @see: https://github.com/amitrou/Spearman-Correlation
   */
  public static function correlation_spearman(array $data1, array $data2): float|int|bool
  {
    if (count($data1) == 0 && count($data2) == 0) {
      return 1;
    }
        
    if (count($data1) != count($data2)) {
      return false;
    }
        
    $relation1 = [];
    for ($i = 0; $i < count($data1); $i++) {
      $relation1[] = $i;
    }
    $relation2 = $relation1;
        
    // keeping index associations
    array_multisort($data1, $relation1);
    array_multisort($data2, $relation2);

    $ranking1 = self::ranking($data1);
    $ranking2 = self::ranking($data2);
        
    // Back to prevous orders/relationships
    array_multisort($relation1, $ranking1);
    array_multisort($relation2, $ranking2);
        
    $distances = self::distances($ranking1, $ranking2);
    return self::coefficient($distances);
  }
    
  /**
   * Compute the coefficient of an array of distances.
   *
   * -- parameters:
   * @param list<int|float> $distances The input array of values.
   *
   * @return float|int|bool The calculated coefficient.
   *
   * This method is part of the spearman correlation and was originally
   * written by Alejandro Mitrou under the GPL license.
   *
   * @see: http://www.wisetonic.com/
   * @see: https://github.com/amitrou/Spearman-Correlation
   */
  public static function coefficient(array $distances): float|int|bool
  {
    if (count($distances) < 1) {
      trigger_error("The array has zero elements", E_USER_WARNING);
      return false;
    }
        
    $size = count($distances);
    $sum  = 0;
    for ($i = 0; $i < $size; $i++) {
      $sum += $distances[$i];
    }
    return 1 - (6 * $sum / (pow($size, 3) - $size));
  }
    
  /**
   * Return an array of distances computed from the values of the two
   * given arrays.
   *
   * -- parameters:
   * @param list<int|float> $ranking1 The first input array of values.
   * @param list<int|float> $ranking2 The second input array of values.
   *
   * @return list<int|float> An array of computed distances.
   *
   * This method is part of the spearman correlation and was originally
   * written by Alejandro Mitrou under the GPL license.
   *
   * @see: http://www.wisetonic.com/
   * @see: https://github.com/amitrou/Spearman-Correlation
   */
  public static function distances(array $ranking1, array $ranking2): array
  {
    $distances = [];
    foreach (arrays::zip($ranking1, $ranking2) as [$r1, $r2]) {
      $distances[] = pow($r1 - $r2, 2);
    }
    return $distances;
  }
    
  /**
   * This method is part of the spearman correlation and were originally
   * written by Alejandro Mitrou under the GPL license.
   *
   * @internal
   *
   * -- parameters:
   * @param list<int|float> $data The input array of values.
   *
   * @return ?list<int|float>
   *
   * @see http://www.wisetonic.com/
   * @see https://github.com/amitrou/Spearman-Correlation
   */
  protected static function ranking(array $data): ?array
  {
    $ranking    = [];
    $prevValue  = '';
    $eqCount    = 0;
    $eqSum      = 0;
    $rankingPos = 1;
        
    foreach ($data as $key => $value) {
      if ($value == '') {
        return null;
      }

      if ($value != $prevValue) {
        if ($eqCount > 0) {
          // Go back to set mean as ranking
          foreach (sequence(0, $eqCount) as $j) {
            $ranking[$rankingPos - 2 - $j] = $eqSum / ($eqCount+1);
          }
        }
        $eqCount = 0;
        $eqSum = $rankingPos;
      } else {
        $eqCount++;
        $eqSum += $rankingPos;
      }

      // Keeping $data after sorting order
      $ranking[] = $rankingPos;
      $prevValue = $value;
      $rankingPos++;
    }
        
    // Go back to set mean as ranking in case last value has repetitions
    for ($j = 0; $j <= $eqCount; $j++) {
      $ranking[$rankingPos - 2 - $j] = $eqSum / ($eqCount+1);
    }
        
    return $ranking;
  }
    
  /**
   * Round the given value(s) to the desired precision. This method performs a double round, first rounding
   * the float using the preferred mode of rounding, then converting the result to a string with number_format
   * in order to hold the precision.
   *
   * -- parameters:
   * @param float|int|string|list<int|float|string> $value The value to be rounded. If an array is passed in then each element within is rounded.
   * @param int $precision The number of decimal digits to round to.
   * @param int-mask<1,2,3,4> $mode<1|2|3|4> The rounding mode used.
   *
   * @return string|list<string> The rounded value, or array of rounded values (depending on the input).
   *
   * @see Both [round()](https://www.php.net/manual/en/function.round.php) and [number_format()](https://www.php.net/manual/en/function.number-format.php) for further information on rounding modes and how the rounding engine works.
   */
  public static function nf_round(float|array|int|string $value, int $precision, int $mode = PHP_ROUND_HALF_UP): string|array
  {
    if (is_array($value)) {
      return array_map(fn ($v) => self::nf_round($v, $precision, $mode), $value);
    }
    return number_format(num:round((float)$value, $precision, $mode), decimals:$precision, thousands_separator:'');
  }
}
