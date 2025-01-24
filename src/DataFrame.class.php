<?php

declare(strict_types=1);

namespace sqonk\phext\datakit;

/**
 *
 * Data Kit
 *
 * @package		phext
 * @subpackage	datakit
 * @version		1
 *
 * @license		MIT see license.txt
 * @copyright	2019 Sqonk Pty Ltd.
 *
 *
 * This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

use Error;
use sqonk\phext\plotlib\BulkPlot;
use sqonk\phext\core\arrays;
use sqonk\phext\core\strings;

/**
 * The DataFrame is a class inspired by, and loosely based off of, a class by the
 * same name from the Pandas library in Python. It specialises in working with 2
 * dimensional arrays (rows and columns) that may originate from data sources such
 * as CSV files or data fetched from a relational database.
 *
 * Various basic statistical and mathematical functions are provided as well numerous
 * methods for transforming and manipulating the underlying data and presentation
 * thereof.
 *
 * Adheres to interfaces, ArrayAccess, Countable, IteratorAggregate
 *
 * @implements \IteratorAggregate<mixed, array<string, string>>
 * @implements \ArrayAccess<mixed, array<string, string>>
 */
final class DataFrame implements \ArrayAccess, \Countable, \IteratorAggregate
{
  /**
   * @var array<mixed, array<mixed, mixed>>
   */
  protected array $data;

  /**
   * @var ?list<string>
   */
  protected ?array $headers = [];

  /**
   * @var array<string, mixed>
   */
  protected array $keep = [];

  /**
   * @var array<string, callable>
   */
  protected array $transformers = [];
  protected string $indexHeader = '';
  protected bool $showHeaders = true;
  protected bool $showGenericIndexes = true;

  /**
   *   Static equivalent of `new DataFrame`.
   *
   * -- parameters:
   * @param array<mixed, array<mixed, mixed>> $data The array of data. Unless `$isVerticalDataSet` is TRUE, the array should be an array of rows. Each row is an associative array where the keys correspond to the headers of each column.
   * @param ?list<string> $headers An optional custom set of column headers.
   * @param bool $isVerticalDataSet When set to TRUE the $data array is interpreted as a vertical series of columns instead of rows. Defaults to FALSE.
   *
   * @return DataFrame A new DataFrame.
   */
  public static function make(array $data, ?array $headers = null, bool $isVerticalDataSet = false): DataFrame
  {
    return new DataFrame($data, $headers, $isVerticalDataSet);
  }

  public static function empty_frames(): bool
  {
    return defined('EMPTY_DATAFRAMES') && constant('EMPTY_DATAFRAMES');
  }

  // -------- Class Interfaces

  public function getIterator(): \Iterator
  {
    return new \ArrayIterator($this->data());
  }

  public function offsetSet(mixed $index, mixed $row): void
  {
    $keys = array_keys($this->data());
    if ($index === LAST_ROW) {
      $index = arrays::last($keys);
    } elseif ($index === FIRST_ROW) {
      $index = $keys[0];
    }

    if ($index === null) {
      $this->data[] = $row;
    } else {
      $this->data[$index] = $row;
    }
  }

  public function offsetExists(mixed $index): bool
  {
    return $this->row($index) !== null;
  }

  public function offsetUnset(mixed $index): void
  {
    $keys = array_keys($this->data());
    if ($index === LAST_ROW) {
      $index = arrays::last($keys);
    } elseif ($index === FIRST_ROW) {
      $index = $keys[0];
    }

    $this->drop_rows($index, null, true);
  }

  public function offsetGet(mixed $index): mixed
  {
    return $this->row($index);
  }

  /**
   * Converting the DataFrame to a string produces the report.
   *
   * @see report()
   */
  public function __tostring(): string
  {
    return $this->report();
  }

  // ------- Main class methods

  /**
   * Construct a new DataFrame with the provided data. You may optionally provide the
   * set of column headers in the second parameter. If you choose to do this then they
   * should match the keys in the array.
   *
   * -- parameters:
   * @param array<mixed, array<mixed, mixed>> $data The array of data. Unless `$isVerticalDataSet` is TRUE, the array should be an array of rows. Each row is an associative array where the keys correspond to the headers of each column.
   * @param ?list<string> $headers An optional custom set of column headers.
   * @param bool $isVerticalDataSet When set to TRUE the $data array is interpreted as a vertical series of columns instead of rows. Defaults to FALSE.
   *
   * Standard data array format:
   *
   * ``` php
   * [
   *     ['col 1' => value, 'col 2' => value ... 'col Nth' => value],
   *     ['col 1' => value, 'col 2' => value ... 'col Nth' => value]
   * ]
   * ```
   *
   * Data Array format for vertical data sets:
   *
   * ``` php
   * [
   *     'col 1' => [... values],
   *     'col 2' => [... values],
   *     'col Nth' => [... values]
   * ]
   * ```
   */
  public function __construct(?array $data = null, ?array $headers = null, bool $isVerticalDataSet = false)
  {
    if ($data === null) {
      if (! self::empty_frames()) {
        throw new \InvalidArgumentException("The data array can not be null. A valid array must be given. If you need to work with empty DataFrames you can do so by defining 'EMPTY_DATAFRAMES' as TRUE.");
      } else {
        $data = [];
      }
    } elseif (! self::empty_frames() and count($data) == 0 and ($headers === null or count($headers) == 0)) {
      throw new \LengthException('A DataFrame needs at least one row of data.');
    }

    if ($isVerticalDataSet) {
      # data has been supplied as a keyed set of columns, translate to rows.
      $headers = array_keys($data);
      $rows = [];
      foreach (arrays::zip(...array_values($data)) as $values) {
        $rows[] = array_combine($headers, $values);
      }
      $data = &$rows;
    }

    $this->data = $data;
    $this->headers = $headers;
    if (! $this->headers and count($data) > 0) {
      $indexes = array_keys($this->data);
      $this->headers = array_keys($this->data[$indexes[0]]);
    }
  }

  /**
   * Produce an exact replica of the dataframe.
   */
  public function copy(): static
  {
    return $this->clone($this->data());
  }

  /**
   * Produce a copy of the dataframe consisting of only the supplied data. All other information such as transformers and header settings remain the same.
   *
   * -- parameters:
   * @param array<mixed, array<mixed, mixed>> $data The array of data. Unless `$isVerticalDataSet` is TRUE, the array should be an array of rows. Each row is an associative array where the keys correspond to the headers of each column.
   * @param ?list<string> $headers An optional custom set of column headers.
   *
   * @return DataFrame A copy of the DataFrame.
   */
  public function clone(array $data, ?array $headers = null): DataFrame
  {
    if (! $headers) {
      $headers = $this->headers;
    }

    foreach ($this->keep as $h => $index) {
      if (! array_key_exists(key: $h, array: $headers)) {
        $value = $this->row($index)[$h];
        foreach ($data as &$row) {
          $row[$h] = $value;
        }
        $headers[] = $h;
      }
    }

    $copy = new DataFrame($data, $headers);
    $copy->transformers = $this->transformers;
    $copy->indexHeader = $this->indexHeader;
    $copy->showGenericIndexes = $this->showGenericIndexes;
    $copy->showHeaders = $this->showHeaders;
    return $copy;
  }

  /**
   * Keep a specific value (at a single index) for one or more columns when the data frame is
   * cloned. If the data being cloned already has values for a particular column requested
   * then it maintains its copy and the data is not overwritten.
   *
   * This method is useful for holding onto certain columns when performing a reductive
   * operation that produces a new frame with a single row.
   *
   * -- parameters:
   * @param mixed $index The row index to take the corresponding column values from.
   * @param string ...$columns One or more columns to take the values from at the corresponding row index.
   *
   * @return self A reference the object.
   */
  public function keep(mixed $index, string ...$columns): self
  {
    foreach ($columns as $h) {
      $this->keep[$h] = $index;
    }
    return $this;
  }

  /**
   * Whether or not the DataFrame should display the column headers when it is printed. The default is TRUE.
   */
  public function display_headers(?bool $display = null): static|bool
  {
    if ($display === null) {
      return $this->showHeaders;
    }

    $this->showHeaders = $display;
    return $this;
  }

  /**
   * Whether or not the DataFrame should display the row indexes that sequentially numerical when it is printed.
   *
   * The default is TRUE.
   *
   * This is automatically disabled for pivoted DataFrames.
   */
  public function display_generic_indexes(?bool $display = null): static|bool
  {
    if ($display === null) {
      return $this->showGenericIndexes;
    }

    $this->showGenericIndexes = $display;
    return $this;
  }

  /**
   * Set or get the column header currently or to be used as the row indexes.
   *
   * ** You should not need to set this.
   *
   * See reindex_rows_with_column() instead.
   */
  public function index(?string $indexHeader = null): static|string
  {
    if ($indexHeader === null) {
      return $this->indexHeader;
    }

    $this->indexHeader = $indexHeader;
    return $this;
  }

  /**
   * @internal
   *
   * Used to set or get the full list of display transformers.
   *
   * You should not need to call this function under normal circumstances.
   *
   * @see apply_display_transformer() instead.
   *
   * -- parameters:
   * @param ?array<string, callable> $transformers
   *
   * @return static|array<string, callable>
   */
  public function transformers(?array $transformers = null): static|array
  {
    if ($transformers === null) {
      return $this->transformers;
    }

    $this->transformers = $transformers;
    return $this;
  }

  /**
   * Returns TRUE if and only if all values within the given column ontain a valid number.
   */
  public function column_is_numeric(string $column): bool
  {
    $count = 0;
    foreach ($this->data as &$row) {
      if (isset($row[$column]) && is_numeric($row[$column])) {
        $count++;
      }
      if ($count > 1) {
        return true;
      }
    }
    return false;
  }

  /**
   * Return the associative array containing all the data within the DataFrame.
   *
   * @return array<mixed, array<mixed, mixed>>
   */
  public function data(): array
  {
    return $this->data;
  }

  /**
   *	Flatten the DataFrame into a native array.
   *
   * -- parameters:
   * @param bool $includeIndex If TRUE then use the DataFrame indexes as the keys in the array.
   * @param string|list<string> $columns One or more columns that should be used in the resulting array, all columns if null is supplied. Defaults to all columns.
   *
   * @return array<mixed> An array containing all of the data in the object.
   */
  public function flattened(bool $includeIndex = true, string|array $columns = ''): array
  {
    $columns = $this->determineColumns($columns);
    $out = [];
    foreach ($this->data as $index => $row) {
      $r = [];
      if ($includeIndex) {
        $r[] = $index;
      }
      foreach ($columns as $h) {
        $r[] = $row[$h] ?? null;
      }
      $out[] = $r;
    }

    return $out;
  }

  /**
   * Return the row at $index.
   *
   * @return array<string, mixed>
   */
  public function row(mixed $index): array
  {
    if ($index === LAST_ROW) {
      $row = arrays::last($this->data);
    } elseif ($index === FIRST_ROW) {
      $row = arrays::first($this->data);
    } else {
      $row = $this->data[$index] ?? null;
    }

    return $row;
  }

  /**
   * Return an array of all the current row indexes.
   *
   * @return list<string|int|float>
   */
  public function indexes(): array
  {
    return array_keys($this->data);
  }

  /**
   * All column headers currently in the DataFrame.
   *
   * @return list<string>
   */
  public function headers(): array
  {
    return $this->headers;
  }

  /**
   * Return a copy of the DataFrame only containing the number
   * of rows from the start as specified by $count.
   */
  public function head(int $count): DataFrame
  {
    if ($count >= count($this->data)) {
      return $this->clone($this->data);
    }

    $slice = array_slice($this->data, 0, $count, true);
    return $this->clone($slice);
  }

  /**
   * Return a copy of the DataFrame only containing the number
   * of rows from the end as specified by $count.
   */
  public function tail(int $count): DataFrame
  {
    $total = count($this->data);
    if ($count >= $total) {
      return $this->clone($this->data);
    }

    $slice = array_slice($this->data, $total - $count, $count, true);
    return $this->clone($slice);
  }

  /**
   * Return a copy of the DataFrame only containing the rows
   * starting from $start through to the given length.
   */
  public function slice(int $start, ?int $length = null): DataFrame
  {
    $total = count($this->data);
    if ($start >= $total) {
      throw new \InvalidArgumentException("Start of slice is greater than the length of the array.");
    }
    if ($length and $start + $length > $total - 1) {
      $length = null;
    }

    $slice = array_slice($this->data, $start, $length, true);
    return $this->clone($slice);
  }

  /**
   * Return a copy of the DataFrame containing a random
   * subset of the rows. The minimum and maximum values
   * can be supplied to focus the random sample to a
   * more constrained subset.
   */
  public function sample(int $minimum, ?int $maximum = null): DataFrame
  {
    $max = count($this->data);
    if ($maximum !== null && $maximum < $max) {
      $max = $maximum;
    }

    $start = $max + 1;
    while ($max - $start < $minimum) {
      $start = rand(0, $max);
    }

    $length = rand($minimum, $max - $start);
    return $this->slice($start, $length);
  }

  /**
   * Change the name of a column within the DataFrame. If $inPlace
   * is TRUE then this operation modifies the receiver otherwise
   * a copy is returned.
   */
  public function change_header(string $column, string $newName, bool $inPlace = false): DataFrame
  {
    if ($inPlace) {
      foreach ($this->data as &$row) {
        if (isset($row[$column])) {
          $row[$newName] = $row[$column];
          unset($row[$column]);
        }
      }
      $indexes = array_keys($this->data);
      $this->headers = array_keys($this->data[$indexes[0]]);
      if (isset($this->transformers[$column])) {
        $this->transformers[$newName] = $this->transformers[$column];
        unset($this->transformers[$column]);
      }
      return $this;
    } else {
      $data = $this->data;
      foreach ($data as &$row) {
        if (isset($row[$column])) {
          $row[$newName] = $row[$column];
          unset($row[$column]);
        }
      }
      $copy = $this->clone($data);
      $indexes = array_keys($data);
      $headers = array_keys($data[$indexes[0]]);
      $copy->headers = $headers;
      if (isset($copy->transformers[$column])) {
        $copy->transformers[$newName] = $copy->transformers[$column];
        unset($copy->transformers[$column]);
      }
      return $copy;
    }
  }

  /**
   * Reindex the DataFrame using the provided labels.
   *
   * -- parameters:
   * @param list<string|int|float> $labels The values to use as the indexes.
   * @param bool $inPlace If TRUE then this operation modifies the receiver otherwise a copy is returned.
   *
   * @return DataFrame Either the receiver or a copy.
   */
  public function reindex_rows(array $labels, bool $inPlace = false): DataFrame
  {
    $values = array_values($this->data);
    if (count($labels) > count($values)) {
      $labels = array_slice($labels, 0, count($values));
    } elseif (count($labels) < count($values)) {
      throw new \LengthException('The array of new indexes provided is less than the total rows in the dataframe.');
    }

    $data = array_combine($labels, $values);
    if ($inPlace) {
      $this->data = $data;
      return $this;
    }

    return $this->clone($data);
  }

  /**
   * Push one of the columns out to become the row index. If $inPlace
   * is TRUE then this operation modifies the receiver otherwise
   * a copy is returned.
   */
  public function reindex_rows_with_column(string $column, bool $inPlace = false): DataFrame
  {
    $df = $this->reindex_rows($this->values($column), $inPlace);
    $df->indexHeader = $column;
    return $df->drop_columns($column, true);
  }

  /**
   * Filter the DataFrame using a basic value comparison in strict mode.
   * 
   * @param string|int|float $value The value to filter by.
   * @param string $column The column to do the filtering on.
   * @param bool $strictCompare When TRUE the value comparison is strict and also compares data type.
   * @return null|DataFrame 
   * A new dataframe containing the filtered subset. Depending on whether EMPTY_FRAMES has been declared
   * this method will either return an empty frame or NULL when no results are found.
   * @throws Error 
   */
  public function filter_by_value(string|int|float $value, string $column, bool $strictCompare = false): ?DataFrame
  {
    $filtered = [];
    foreach ($this->data as $index => $row) {
      if (isset($row[$column])) {
        if (($strictCompare && $row[$column] === $value) || (!$strictCompare && $row[$column] == $value)) {
          $filtered[$index] = $row;
        }
      }
    }
    return (count($filtered) > 0 or self::empty_frames()) ? $this->clone($filtered) : null;
  }

  /**
   * Filter the DataFrame using the provided callback and one or
   * more columns. If no columns are specified then the operation
   * applies to all.
   * 
   * Callback format: `myFunc($value, $column, $rowIndex): bool`
   *
   * For a row to make it into the filtered set then only ONE
   * of the columns need to equate to true from the callback.
   * 
   * @param callable $callback The callback to use to determine which rows pass.
   * @param string ...$columns The columns to filter by.
   * @return null|DataFrame 
   * A new dataframe containing the filtered subset. Depending on whether EMPTY_FRAMES has been declared
   * this method will either return an empty frame or NULL when no results are found.
   * @throws Error 
   */
  public function filter(callable $callback, string ...$columns): ?DataFrame
  {
    $columns = $this->determineColumns($columns);

    $filtered = [];
    foreach ($this->data as $index => $row) {
      $pass = false;
      foreach ($columns as $column) {
        if (isset($row[$column]) and $callback($row[$column], $column, $index)) {
          $pass = true;
          break;
        }
      }
      if ($pass) {
        $filtered[$index] = $row;
      }
    }

    return (count($filtered) > 0 or self::empty_frames()) ? $this->clone($filtered) : null;
  }

  /**
   * Filter the DataFrame using the provided callback and one or
   * more columns. If no columns are specified then the operation
   * applies to all.
   *
   * Callback format: `myFunc($value, $column, $rowIndex) -> bool`
   *
   * For a row to make it into the filtered set then ALL
   * of the columns need to equate to true from the callback.
   */
  public function unanfilter(callable $callback, string ...$columns): ?DataFrame
  {
    $columns = $this->determineColumns($columns);

    $filtered = [];
    foreach ($this->data as $index => $row) {
      $pass = true;
      foreach ($columns as $column) {
        if (! isset($row[$column]) or ! $callback($row[$column], $column, $index)) {
          $pass = false;
          break;
        }
      }
      if ($pass) {
        $filtered[$index] = $row;
      }
    }
    return (count($filtered) > 0 or self::empty_frames()) ? $this->clone($filtered) : null;
  }

  /**
   * Filter the DataFrame using the provided callback and one or
   * more columns. If no columns are specified then the operation
   * applies to all.
   *
   * Callback format: `myFunc($row, $rowIndex) -> bool`
   *
   * This function differs from filter() and unanfilter() in that
   * it passes the whole row to the callback. This is useful
   * if your condition of inclusion requires cross comparing
   * data across columns within the row.
   */
  public function ufilter(callable $callback): ?DataFrame
  {
    $filtered = [];
    foreach ($this->data as $index => $row) {
      if ($callback($row, $index)) {
        $filtered[$index] = $row;
      }
    }
    return (count($filtered) > 0 or self::empty_frames()) ? $this->clone($filtered) : null;
  }

  /**
   * Sort the DataFrame via one or more columns.
   *
   * If the last parameter passed in is either `ASCENDING` or `DESCENDING`
   * then it will determine the direction in which the dataframe
   * is ordered. The default is `ASCENDING`.
   */
  public function sort(string|bool ...$columns): DataFrame
  {
    $asc = true;
    if (count($columns) > 0 && is_bool(arrays::last($columns))) {
      $asc = array_pop($columns);
    }

    $columns = $this->determineColumns($columns);

    uasort($this->data, function ($a, $b) use ($columns, $asc) {
      $cmp = 0;
      if (! $asc) {
        [$a, $b] = [$b, $a];
      }
      foreach ($columns as $column) {
        $cmp = arrays::get($a, $column) <=> arrays::get($b, $column);
        if ($cmp !== 0) {
          break;
        } // non-0 result will break out.
      }
      return $cmp;
    });
    return $this;
  }

  /**
   * Sort the DataFrame using a callback and one or more columns.
   *
   * Callback format: `myFunc($value1, $value2, $column) -> bool`
   */
  public function usort(callable $callback, string ...$columns): DataFrame
  {
    $columns = $this->determineColumns($columns);
    if (count($columns) == 0) {
      throw new \LengthException("sorting requires at least one column");
    }

    uasort($this->data, function ($a, $b) use ($columns, $callback) {
      $cmp = 0;
      foreach ($columns as $column) {
        $cmp = $callback(arrays::get($a, $column), arrays::get($b, $column), $column);
        if ($cmp !== 0) {
          break;
        }
      }
      return $cmp;
    });
    return $this;
  }

  /**
   * Return an array containing both the number of rows and columns.
   *
   * @return array{int, int}
   */
  public function shape(): array
  {
    $cols = 0;
    foreach ($this->data as $row) {
      $cnt = count($row);
      if ($cnt > $cols) {
        $cols = $cnt;
      }
    }
    return [count($this->data), $cols];
  }


  /**
   * Return the count of the number of rows of either a single column
   * or all columns.
   * 
   * @return int|static 
   * If a column is specified then return the number of rows
   * containing a value for it. If no column is given then return a 
   * new DataFrame containing the counts for all columns.
   */
  public function size(?string $column = null): int|static
  {
    if ($column) {
      return count($this->values($column, false));
    }

    $r = [];
    foreach ($this->headers as $h) {
      $values = $this->values($h, false);
      $r[$h] = count($values);
    }
    return $this->clone([$r]);
  }

  /**
   * Return the number of rows.
   */
  public function count(): int
  {
    return count($this->data);
  }

  /**
   * @internal
   *
   * -- parameters:
   * @param list<string>|string|null $columns
   *
   * @return list<string>
   */
  protected function determineColumns(array|string|null $columns): array
  {
    if ($columns === null || $columns === '' || (is_array($columns) && count($columns) == 0)) {
      $columns = $this->headers;
    } elseif (! is_array($columns)) {
      $columns = [$columns];
    }

    if (! $columns) {
      $columns = [];
    }

    return $columns;
  }

  /**
   * Return all values for one or more columns.
   *
   * -- parameters:
   * @param list<string>|string|null $columns The column(s) to acquire the values for.
   * @param bool $filterNAN If TRUE then omit values that are NULL.
   *
   * @return list<mixed>|list<list<mixed>> Either a singular list of values when one column is given, or a two dimensional array when two or more columns are requested.
   */
  public function values(array|string|null $columns = null, bool $filterNAN = true): array
  {
    $columns = $this->determineColumns($columns);

    $r = [];
    foreach ($columns as $h) {
      $values = [];
      foreach ($this->data as $row) {
        $v = array_key_exists($h, $row) ? $row[$h] : null;
        if ($v !== null || ! $filterNAN) {
          $values[] = $v;
        }
      }
      $r[] = $values;
    }
    return (count($r) == 1) ? $r[0] : $r;
  }

  /**
   * Return the data array with all values parsed by any registered
   * transformers.
   *
   * If you wish to output a report to something else other than the
   * command line then this method will allow you to present the data
   * as desired.
   *
   * -- parameters:
   * @param string ...$columns The columns to use in the report.
   *
   * @return array{array<mixed>, list<string>} A two-element array. The first element contains an array of the compiled data and second is a copy of the column names requested (potentially adjusted).
   */
  public function report_data(string ...$columns): array
  {
    $columns = $this->determineColumns($columns);
    $data = [];

    // filters the transformers to the set applicable for this report.
    $trs = [];
    foreach ($this->transformers as $h => $tf) {
      if (in_array(haystack: $columns, needle: $h) || $this->indexHeader == $h) {
        $trs[$h] = $tf;
      }
    }

    foreach ($this->data as $index => $row) {
      foreach ($trs as $h => $tf) {
        if (isset($row[$h])) {
          $row[$h] = $tf($row[$h]);
        } elseif ($this->indexHeader == $h) {
          $index = $tf($index);
        }
      }

      $data[$index] = $row;
    }

    return [$data, $columns];
  }

  /**
   * Produce a formatted string, suitable for outputting to
   * the command line or browser, detailing all rows and
   * the desired columns. If no columns are specified then
   * all columns are used.
   */
  public function report(string ...$columns): string
  {
    [$data, $columns] = $this->report_data(...$columns);

    return strings::columnize($data, $columns, $this->showHeaders, $this->showGenericIndexes);
  }

  /**
   * Print to stdout the report for this DataFrame.
   *
   * See: report()
   */
  public function print(string ...$columns): void
  {
    println($this->report(...$columns));
  }

  /**
   * Provide a maximum or minimum (or both) constraint for the values on column.
   *
   * If a row's value for the column exceeds that constraint then it will be set
   * to the constraint.
   *
   * If either the lower or upper constraint is not needed then passing in
   * null will ignore it.
   *
   * If no column is specified then the constraints apply to all columns.
   *
   * If $inPlace is TRUE then this operation modifies the receiver otherwise
   * a copy is returned.
   */
  public function clip(int|float|null $lower, int|float|null $upper, ?string $column = null, bool $inplace = false): DataFrame
  {
    if ($inplace) {
      foreach ($this->data as &$row) {
        if ($column !== null) {
          if (isset($row[$column])) {
            $value = $row[$column];
            if ($lower !== null && is_numeric($value) && $value < $lower) {
              $row[$column] = $lower;
            } elseif ($upper !== null && is_numeric($value) && $value > $upper) {
              $row[$column] = $upper;
            }
          }
        } else {
          foreach ($row as $key => $value) {
            if ($lower !== null && is_numeric($value) && $value < $lower) {
              $row[$key] = $lower;
            } elseif ($upper !== null && is_numeric($value) && $value > $upper) {
              $row[$key] = $upper;
            }
          }
        }
      }
      return $this;
    } else {
      $modified = [];
      foreach ($this->data as $row) {
        if ($column !== null) {
          if (isset($row[$column])) {
            $value = $row[$column];
            if ($lower !== null && is_numeric($value) && $value < $lower) {
              $row[$column] = $lower;
            } elseif ($upper !== null && is_numeric($value) && $value > $upper) {
              $row[$column] = $upper;
            }
          }
        } else {
          foreach ($row as $key => $value) {
            if ($lower !== null && is_numeric($value) && $value < $lower) {
              $row[$key] = $lower;
            } elseif ($upper !== null && is_numeric($value) && $value > $upper) {
              $row[$key] = $upper;
            }
          }
        }
        $modified[] = $row;
      }
      return $this->clone($modified);
    }
  }

  /**
   * Remove any rows where the value of the provided column exeeds the provided
   * lower or upper boundary, for a given column.
   *
   * If either the lower or upper constraint is not needed then passing in
   * NULL will ignore it.
   *
   * If no column is specified then the filter applies to all columns.
   *
   * If $inPlace is TRUE then this operation modifies the receiver otherwise
   * a copy is returned.
   */
  public function prune(int|float|null $lower, int|float|null $upper, ?string $column = null, bool $inplace = false): ?DataFrame
  {
    if ($inplace) {
      foreach ($this->data as &$row) {
        if ($column !== null) {
          if (isset($row[$column])) {
            $value = $row[$column];
            if ($lower !== null && is_numeric($value) && $value < $lower) {
              unset($row[$column]);
            }
            if ($upper !== null && is_numeric($value) && $value > $upper) {
              unset($row[$column]);
            }
          }
        } else {
          foreach ($row as $key => $value) {
            if ($lower !== null && is_numeric($value) && $value < $lower) {
              unset($row[$key]);
            } elseif ($upper !== null && is_numeric($value) && $value > $upper) {
              unset($row[$key]);
            }
          }
        }
      }
      return $this;
    } else {
      $modified = [];
      foreach ($this->data as $row) {
        if ($column !== null) {
          if (isset($row[$column])) {
            $value = $row[$column];
            if ($lower !== null && is_numeric($value) && $value < $lower) {
              unset($row[$column]);
            } elseif ($upper !== null && is_numeric($value) && $value > $upper) {
              unset($row[$column]);
            }
          }
        } else {
          foreach ($row as $key => $value) {
            if ($lower !== null && is_numeric($value) && $value < $lower) {
              unset($row[$key]);
            } elseif ($upper !== null && is_numeric($value) && $value > $upper) {
              unset($row[$key]);
            }
          }
        }
        $modified[] = $row;
      }
      return $this->clone($modified);
    }
  }

  /**
   * Return a new DataFrame containing the rows where the values of the
   * given column exceed a lower and/or upper boundary.
   *
   * If either the lower or upper constraint is not needed then passing in
   * NULL will ignore it.
   *
   * If no column is specified then the filter applies to all columns.
   */
  public function oob(int|float|null $lower, int|float|null $upper, ?string $column = null): ?DataFrame
  {
    $data = [];
    $count = count($this->data);
    $keys = array_keys($this->data);
    $cols = null;
    for ($i = 0; $i < $count; $i++) {
      $index = $keys[$i];
      $row = $this->data[$index];

      if ($column !== null) {
        $cols = ['column', 'lower', 'upper'];
        if (isset($row[$column])) {
          $value = $row[$column];
          $r = ['column' => $column, 'lower' => null, 'upper' => null];

          if ($lower !== null && is_numeric($value) && $value < $lower) {
            $r['lower'] = $value;
          } elseif ($upper !== null && is_numeric($value) && $value > $upper) {
            $r['upper'] = $value;
          }

          if ($r['lower'] !== null or $r['upper'] !== null) {
            $data[$index] = $r;
          }
        }
      } else {
        $cols = ['index', 'column', 'lower', 'upper'];
        $r = [];
        foreach ($row as $key => $value) {
          if ($lower !== null && is_numeric($value) && $value < $lower) {
            $r = [
              'index' => $index,
              'column' => $key,
              'lower' => $value,
              'upper' => null
            ];
            $data[] = $r;
          } elseif ($upper !== null && is_numeric($value) && $value > $upper) {
            $r = [
              'index' => $index,
              'column' => $key,
              'upper' => $value,
              'lower' => null,
            ];
            $data[] = $r;
          }
        }
      }
    }
    return (count($data) > 0 or self::empty_frames()) ? new DataFrame($data, $cols) : null;
  }

  /**
   * Return a new 2-column DataFrame containing both the start and end points
   * where values for a specific column exceed a given threshold.
   *
   * $direction can be `OOB_LOWER`, `OOB_UPPER` or `OOB_ALL` to determining if
   * the threshold is calculated as a minimum boundary, maximum boundary or
   * either.
   *
   * Where `oob()` simply returns all the rows that exceed the threshold, this
   * method will return a DataFrame of regions, where the start and end
   * values refer to the row indexes of the current DataFrame.
   */
  public function oob_region(int|float $theshhold, int $direction, string $column): ?DataFrame
  {
    $data = [];
    $current = null;
    $last = null;
    foreach ($this->data as $index => $row) {
      if (! isset($row[$column])) {
        continue;
      }

      $value = $row[$column];
      if (
        is_numeric($value) and
        (($direction == OOB_LOWER || $direction == OOB_ALL) && $value < $theshhold) or
        (($direction == OOB_UPPER || $direction == OOB_ALL) && $value > $theshhold)
      ) {
        if (! $current) {
          $current = ['start' => $index];
          $last = null;
        } else {
          $last = $index;
        }
      } elseif ($current) {
        $current['end'] = $last ?? $current['start'];
        $data[] = $current;
        $current = null;
      }
    }

    if ($current) {
      // terminate final region.
      $current['end'] = $last ?? $current['start'];
      $data[] = $current;
    }

    return (count($data) > 0 or self::empty_frames()) ? new DataFrame($data, ['start', 'end']) : null;
  }

  /**
   * Return a new 3-column DataFrame containing areas in the current where
   * running values in a column exceed the given amount.
   *
   * For example, if you have a column of timestamps and those timestamps
   * typically increase by N minutes per row, then this method can be used to
   * find possible missing rows where the jump in time is greater than the expected
   * amount.
   *
   * For every row where the given amount is exceeded, a row in the resulting
   * DataFrame will exist where 'start' and 'end' list the values where the
   * gap was found. A third column 'segments' details how many multiples of
   * the amount exist between the values.
   *
   * Providing a column name to $resultColumn allows you to perform the
   * comparison in one column while filling the resulting DataFrame with
   * referenced values from another column.
   */
  public function gaps(int|float $amount, string $usingColumn, string $resultColumn = ''): ?DataFrame
  {
    $result = [];
    $last = null;
    $lastRow = null;
    foreach ($this->data as $index => $row) {
      $current = arrays::safe_value($row, $usingColumn, null);
      if ($current !== null) {
        if ($last !== null && $current - $last > $amount) {
          $count = floor(($current - $last) / $amount) - 1;
          if ($count > 0) {
            $result[] = [
              'start' => $resultColumn ? $lastRow[$resultColumn] : $last,
              'end' => $resultColumn ? $row[$resultColumn] : $current,
              'segments' => $count
            ];
          }
        }
      }
      $last = $current;
      $lastRow = $row;
    }
    return (count($result) > 0 or self::empty_frames()) ? new DataFrame($result, ['start', 'end', 'segments']) : null;
  }

  /**
   * Produces a new DataFrame containing counts for the number of times each value
   * occurs in the given column.
   */
  public function frequency(string $column): DataFrame
  {
    $out = [];
    foreach (array_count_values($this->values($column)) as $col => $value) {
      $out[] = [$column => $col, 'Frequency' => $value];
    }
    return new DataFrame($out);
  }

  /**
   * Returns TRUE if ANY of the rows for a given column match
   * the given value.
   *
   * If no column is specified then the check runs over
   * all columns.
   */
  public function any(mixed $value, ?string $column = null): bool
  {
    foreach ($this->data as $row) {
      if ($column !== null) {
        if (isset($row[$column])) {
          if ($row[$column] == $value) {
            return true;
          }
        }
      } else {
        foreach ($row as $key => $v) {
          if ($v == $value) {
            return true;
          }
        }
      }
    }
    return false;
  }

  /**
   * Returns TRUE if ALL of the rows for a given column match
   * the given value.
   *
   * If no column is specified then the check runs over
   * all columns.
   */
  public function all(mixed $value, ?string $column = null): bool
  {
    foreach ($this->data as $row) {
      if ($column !== null) {
        if (isset($row[$column])) {
          if ($row[$column] != $value) {
            return false;
          }
        }
      } else {
        foreach ($row as $key => $v) {
          if ($v != $value) {
            return false;
          }
        }
      }
    }
    return true;
  }

  /**
   * Convert all values in a given column to their absolute
   * value.
   *
   * If no column is specified then the operation runs over
   * all columns.
   *
   * If $inPlace is TRUE then this operation modifies the current
   * DataFrame, otherwise a copy is returned.
   */
  public function abs(?string $column = null, bool $inplace = false): DataFrame
  {
    if ($inplace) {
      foreach ($this->data as &$row) {
        if ($column !== null) {
          if (isset($row[$column])) {
            $value = $row[$column];
            if (is_numeric($value) && $value < 0) {
              $row[$column] = abs($value);
            }
          }
        } else {
          foreach ($row as $key => $value) {
            if (is_numeric($value) && $value < 0) {
              $row[$key] = abs($value);
            }
          }
        }
      }
      return $this;
    } else {
      $modified = [];
      foreach ($this->data as $row) {
        if ($column !== null) {
          if (isset($row[$column])) {
            $value = $row[$column];
            if (is_numeric($value) && $value < 0) {
              $row[$column] = abs($value);
            }
          }
        } else {
          foreach ($row as $key => $value) {
            if (is_numeric($value) && $value < 0) {
              $row[$key] = abs($value);
            }
          }
        }
        $modified[] = $row;
      }
      return $this->clone($modified);
    }
  }

  /**
   * Compute a standard deviation of one or more columns.
   *
   * If no column is specified then the operation runs over
   * all columns.
   *
   * If exactly one column is supplied then a single value is
   * returned, otherwise a DataFrame of 1 value per column is
   * produced.
   *
   * $sample is passed through to the standard deviation calculation
   * to determine how the result is produced.
   */
  public function std(bool $sample = false, string ...$columns): DataFrame|int|float
  {
    $columns = $this->determineColumns($columns);
    if (count($columns) == 1) {
      $values = $this->values($columns[0]);
      return math::standard_deviation($values, $sample);
    } else {
      $r = [];
      foreach ($columns as $h) {
        $values = $this->values($h);
        $std = math::standard_deviation($values, $sample);
        $r[$h] = $std;
      }
      return $this->clone([$r]);
    }
  }

  /**
   * Compute a sum of one or more columns.
   *
   * If no column is specified then the operation runs over
   * all columns.
   *
   * If exactly one column is supplied then a single value is
   * returned, otherwise a DataFrame of 1 value per column is
   * produced.
   */
  public function sum(string ...$columns): DataFrame|int|float
  {
    $columns = $this->determineColumns($columns);
    if (count($columns) == 1) {
      return array_sum($this->values($columns[0]));
    } else {
      $r = [];
      foreach ($columns as $h) {
        $values = $this->values($h);
        if ($this->column_is_numeric($h)) {
          $r[$h] = array_sum($values);
        } else {
          $r[$h] = (count($values)) ? $values[0] : '';
        }
      }
      return $this->clone([$r]);
    }
  }

  /**
   * Compute the average of one or more columns.
   *
   * If no column is specified then the operation runs over
   * all columns.
   *
   * If exactly one column is supplied then a single value is
   * returned, otherwise a DataFrame of 1 value per column is
   * produced.
   */
  public function avg(string ...$columns): DataFrame|int|float|string
  {
    $columns = $this->determineColumns($columns);
    if (count($columns) == 1) {
      return math::avg($this->values($columns[0]));
    } else {
      $r = [];
      foreach ($columns as $h) {
        if ($this->column_is_numeric($h)) {
          $values = $this->values($h);
          $std = math::avg($values);
          $r[$h] = $std;
        }
      }
      return $this->clone([$r]);
    }
  }

  /**
   * Return the maximum value present for one or more columns.
   *
   * If no column is specified then the operation runs over
   * all columns.
   *
   * If exactly one column is supplied then a single value is
   * returned, otherwise a DataFrame of 1 value per column is
   * produced.
   */
  public function max(string ...$columns): DataFrame|int|float|string
  {
    $columns = $this->determineColumns($columns);
    if (count($columns) == 1) {
      return max($this->values($columns[0]));
    } else {
      $r = [];
      foreach ($columns as $h) {
        if ($this->column_is_numeric($h)) {
          $values = $this->values($h);
          $r[$h] = max($values);
        }
      }
      return $this->clone([$r]);
    }
  }

  /**
   * Return the minimum value present for one or more columns.
   *
   * If no column is specified then the operation runs over
   * all columns.
   *
   * If exactly one column is supplied then a single value is
   * returned, otherwise a DataFrame of 1 value per column is
   * produced.
   */
  public function min(string ...$columns): DataFrame|int|float|string
  {
    $columns = $this->determineColumns($columns);
    if (count($columns) == 1) {
      return min($this->values($columns[0]));
    } else {
      $r = [];
      foreach ($columns as $h) {
        if ($this->column_is_numeric($h)) {
          $values = $this->values($h);
          $r[$h] = min($values);
        }
      }
      return $this->clone([$r]);
    }
  }

  /**
   * Compute a cumulative sum of one or more columns.
   *
   * If no column is specified then the operation runs over
   * all columns.
   *
   * If exactly one column is supplied then a single value is
   * returned, otherwise a DataFrame of 1 value per column is
   * produced.
   *
   * @return DataFrame|list<int|float|null>
   */
  public function cumsum(string ...$columns): DataFrame|array
  {
    $columns = $this->determineColumns($columns);
    if (count($columns) == 1) {
      return math::cumulative_sum($this->values($columns[0]));
    } else {
      $r = $this->data();
      foreach ($columns as $h) {
        if ($this->column_is_numeric($h)) {
          $sum_values = math::cumulative_sum($this->values($h));
          foreach ($sum_values as $i => $v) {
            $r[$i][$h] = $v;
          }
        }
      }
      return $this->clone($r);
    }
  }

  /**
   * Compute the cumulative maximum value for one or more
   * columns.
   *
   * If no column is specified then the operation runs over
   * all columns.
   *
   * If exactly one column is supplied then a single value is
   * returned, otherwise a DataFrame of 1 value per column is
   * produced.
   *
   * @return DataFrame|list<int|float|null>
   */
  public function cummax(string ...$columns): DataFrame|array
  {
    $columns = $this->determineColumns($columns);
    if (count($columns) == 1) {
      return math::cumulative_max($this->values($columns[0]));
    } else {
      $r = $this->data();
      foreach ($columns as $h) {
        if ($this->column_is_numeric($h)) {
          $max_values = math::cumulative_max($this->values($h));
          foreach ($max_values as $i => $v) {
            $r[$i][$h] = $v;
          }
        }
      }
      return $this->clone($r);
    }
  }

  /**
   * Compute the cumulative minimum value for one or more
   * columns.
   *
   * If no column is specified then the operation runs over
   * all columns.
   *
   * If exactly one column is supplied then a single value is
   * returned, otherwise a DataFrame of 1 value per column is
   * produced.
   *
   * @return DataFrame|list<int|float|null>
   */
  public function cummin(string ...$columns): DataFrame|array
  {
    $columns = $this->determineColumns($columns);
    if (count($columns) == 1) {
      return math::cumulative_min($this->values($columns[0]));
    } else {
      $r = $this->data();
      foreach ($columns as $h) {
        if ($this->column_is_numeric($h)) {
          $min_values = math::cumulative_min($this->values($h));
          foreach ($min_values as $i => $v) {
            $r[$i][$h] = $v;
          }
        }
      }
      return $this->clone($r);
    }
  }

  /**
   * Compute the cumulative product for one or more columns.
   *
   * If no column is specified then the operation runs over
   * all columns.
   *
   * If exactly one column is supplied then a single value is
   * returned, otherwise a DataFrame of 1 value per column is
   * produced.
   *
   * @return DataFrame|list<int|float>
   */
  public function cumproduct(string ...$columns): DataFrame|array
  {
    $columns = $this->determineColumns($columns);
    if (count($columns) == 1) {
      return math::cumulative_prod($this->values($columns[0]));
    } else {
      $r = $this->data();
      foreach ($columns as $h) {
        if ($this->column_is_numeric($h)) {
          $prod_values = math::cumulative_prod($this->values($h));
          foreach ($prod_values as $i => $v) {
            $r[$i][$h] = $v;
          }
        }
      }
      return $this->clone($r);
    }
  }

  /**
   * Find the median value for one or more columns.
   *
   * If no column is specified then the operation runs over
   * all columns.
   *
   * If exactly one column is supplied then a single value is
   * returned, otherwise a DataFrame of 1 value per column is
   * produced.
   */
  public function median(string ...$columns): DataFrame|int|float
  {
    $columns = $this->determineColumns($columns);
    if (count($columns) == 1) {
      return math::median($this->values($columns[0]));
    } else {
      $r = [];
      foreach ($columns as $h) {
        if ($this->column_is_numeric($h)) {
          $values = $this->values($h);
          $std = math::median($values);
          $r[$h] = $std;
        }
      }
      return $this->clone([$r]);
    }
  }

  /**
   * Compute the product for one or more columns.
   *
   * If no column is specified then the operation runs over
   * all columns.
   *
   * If exactly one column is supplied then a single value is
   * returned, otherwise a DataFrame of 1 value per column is
   * produced.
   */
  public function product(string ...$columns): DataFrame|int|float
  {
    $columns = $this->determineColumns($columns);
    if (count($columns) == 1) {
      return array_product($this->values($columns[0]));
    } else {
      $r = [];
      foreach ($columns as $h) {
        if ($this->column_is_numeric($h)) {
          $values = $this->values($h);
          $std = array_product($values);
          $r[$h] = $std;
        }
      }
      return $this->clone([$r]);
    }
  }

  /**
   * Compute the variance for one or more columns.
   *
   * If no column is specified then the operation runs over
   * all columns.
   *
   * If exactly one column is supplied then a single value is
   * returned, otherwise a DataFrame of 1 value per column is
   * produced.
   */
  public function variance(string ...$columns): DataFrame|int|float
  {
    $columns = $this->determineColumns($columns);
    if (count($columns) == 1) {
      return math::variance($this->values($columns[0]));
    } else {
      $r = [];
      foreach ($columns as $h) {
        if ($this->column_is_numeric($h)) {
          $values = $this->values($h);
          $std = math::variance($values);
          $r[$h] = $std;
        }
      }
      return $this->clone([$r]);
    }
  }

  /**
   * Normalise one or more columns to a range between 0 and 1.
   *
   * If no column is specified then the operation runs over
   * all columns.
   *
   * If exactly one column is supplied then a single array is
   * returned, otherwise a DataFrame with the given columns is
   * produced.
   *
   * @return DataFrame|list<float>
   */
  public function normalise(string ...$columns): DataFrame|array
  {
    if (count($columns) == 0) {
      throw new \LengthException("At least one column name must be supplied.");
    }

    $columns = $this->determineColumns($columns);
    if (count($columns) == 1) {
      return math::normalise($this->values($columns[0]));
    } else {
      $r = [];
      foreach ($columns as $h) {
        if ($this->column_is_numeric($h)) {
          $r[$h] = math::normalise($this->values($h));
        }
      }

      $i = 0;
      $new = [];
      foreach ($this->data as $index => $row) {
        $newrow = [];
        foreach ($columns as $h) {
          $newrow[$h] = arrays::get($r[$h], $i);
        }

        $i++;
        $new[$index] = $newrow;
      }

      return new DataFrame($new);
    }
  }

  /**
   * Alias of self::normalise().
   *
   * @return DataFrame|list<float>
   */
  public function normalize(string ...$columns): DataFrame|array
  {
    return self::normalise(...$columns);
  }

  /**
   * Alias of self::quantile().
   */
  public function quartile(float $q, ?string $column = null): DataFrame|int|float
  {
    return $this->quantile($q, $column);
  }

  /**
   * Compute the value for a given quantile for one or all columns.
   *
   * If no column is specified then the operation runs over
   * all columns.
   *
   * If exactly one column is supplied then a single value is
   * returned, otherwise a DataFrame of 1 value per column is
   * produced.
   */
  public function quantile(float $q, ?string $column = null): DataFrame|int|float
  {
    if ($column) {
      return math::quantile($this->values($column), $q);
    } else {
      $r = [];
      foreach ($this->headers as $h) {
        if ($this->column_is_numeric($h)) {
          $values = $this->values($h);
          $std = math::quantile($values, $q);
          $r[$h] = $std;
        }
      }
      return $this->clone([$r]);
    }
  }

  /**
   * Round all values in one or more columns up or down to the given decimal point
   * precision. Because of the imprecise nature of floats, the rounding will convert all
   * data points it touches to string format in order to maintain the precision.
   *
   * -- parameters:
   * @param int $precision the number of decimal points values should be rounded to.
   * @param int $mode rounding mode. See [round()](https://www.php.net/manual/en/function.round.php) for available values.
   * @param list<string>|string|null $columns The columns to round. If no column is specified then the operation runs over all columns.
   * @param bool $inPlace If TRUE then this operation modifies the receiver, otherwise a copy is returned.
   *
   * @return DataFrame If $inPlace is FALSE then a copy of the Dataframe is rounded and returned. If TRUE then the DataFrame is directly modified and returns itself.
   *
   * @see [math::nf_round()](math.md#nf_round) for more information on how the rounding is performed.
   */
  public function round(int $precision, int $mode = PHP_ROUND_HALF_UP, array|string|null $columns = null, bool $inPlace = false): DataFrame
  {
    $columns = $this->determineColumns($columns);

    if ($inPlace) {
      $data = &$this->data;
    } else {
      $data = $this->data;
    }

    foreach ($data as &$row) {
      foreach ($columns as $col) {
        $value = $row[$col] ?? null;
        if (is_numeric($value)) {
          $row[$col] = math::nf_round(value: (float)$value, precision: $precision, mode: $mode);
        }
      }
    }

    return $inPlace ? $this : $this->clone($data);
  }

  /**
   * Continually apply a callback to a moving fixed window on the data frame.
   *
   * -- parameters:
   * @param int $window The size of the subset of the data frame that is passed to the callback on each iteration. Note that this is by default the maximum size the window can be. See `$minObservations`.
   * @param callable $callback The callback method that produces a result based on the provided subset of data.
   * @param int $minObservations The minimum number of elements that is permitted to be passed to the callback. If set to 0 the minimum observations will match whatever the window size is set to, thus enforcing the window size. If the value passed in is greater than the window size a warning will be triggered.
   * @param string|list<string> $columns The set of columns to work with. If not provided (or an empty value) then all columns are included.
   * @param string|int|list<int|string|float> $indexes When working horizontally, the collection of rows that should be included. This can either be a singular row or an array of independent indexes. If `$runHorizontal` is `FALSE` then this parameter has no effect.
   * @param bool $runHorizontal When `TRUE` the rolling set will run across columns of the frame. When `FALSE` (the default) the rolling dataset is the series of values down each desired column.
   *
   * Callback format: `myFunc(Vector $rollingSet, mixed $index, string $column) : mixed`
   *
   * @return DataFrame The series of results produced by the callback method.
   */
  public function rolling(
    int $window,
    callable $callback,
    int $minObservations = 0,
    string|array $columns = '',
    string|int|array $indexes = '',
    bool $runHorizontal = false
  ): static {
    if ($window < 1) {
      throw new \InvalidArgumentException("window must be a number greater than 0 ($window given)");
    }
    if ($minObservations > $window) {
      trigger_error("minObservations ($minObservations) is greater than given window ($window). It will be capped to the window size.", E_USER_WARNING);
    }

    $columns = $this->determineColumns($columns);
    $out = [];
    $roller = new Vector;
    $roller->constrain($window);

    if ($minObservations < 1 || $minObservations > $window) {
      $minObservations = $window;
    }

    if ($runHorizontal) {
      // run across the columns along a singular row.
      if (! $indexes) {
        $indexes = $this->indexes();
      } elseif (! is_array($indexes)) {
        $indexes = [$indexes];
      }

      foreach ($indexes as $index) {
        $row = $this->row($index);
        $roller->clear();
        foreach ($columns as $col) {
          $roller->add($row[$col]);

          $r = null;
          if ($roller->count() >= $minObservations) {
            $r = $callback(clone $roller, $index, $col);
          }

          $out[$index][$col] = $r;
        }
      }
    } else {
      // run down the rows along one or more columns.

      foreach ($columns as $col) {
        $roller->clear();

        foreach ($this->data as $index => $row) {
          $roller->add($row[$col]);

          $r = null;
          if ($roller->count() >= $minObservations) {
            $r = $callback(clone $roller, $index, $col);
          }

          $out[$index][$col] = $r;
        }
      }
    }

    return $this->clone(data: $out, headers: $columns);
  }

  /**
   * Run a correlation over one or more columns to find similarities in values.
   *
   * -- parameters:
   * @param string $method Correlation method to use. Accepted values are 'pearson' or 'spearman'.
   * @param ?list<string> $columns Columns to use for the correlation. If no column is specified then the operation runs over all columns.
   *
   * @return DataFrame A matrix of values representing the closeness of the adjoining values.
   */
  public function correlation(string $method, ?array $columns = null): DataFrame
  {
    $columns = $this->determineColumns($columns);
    $matrix = [];

    if (count($columns) < 2) {
      throw new \LengthException("The DataFrame needs to have at least two columns for the requested correlation to work.");
    }
    foreach ($columns as $h) {
      $matrix[] = $this->values($h);
    }

    $r = new DataFrame($this->correlation_matrix($matrix, $method));
    foreach ($columns as $i => $name) {
      $r->change_header((string)$i, $name, true);
    }
    $r->reindex_rows($columns, true);
    return $r;
  }

  /**
   * Alias of correlation().
   *
   * -- parameters:
   * @param string $method Correlation method to use. Accepted values are 'pearson' or 'spearman'.
   * @param list<string> $columns Columns to use for the correlation. If no column is specified then the operation runs over all columns.
   *
   * @return DataFrame A matrix of values representing the closeness of the adjoining values.
   */
  public function corr(string $method, ?array $columns = null): DataFrame
  {
    return $this->correlation($method, $columns);
  }

  /**
   * Column and row structure must be inverted for this to work.
   *
   * @internal
   *
   * @param array<mixed> $matrix
   * @param string $method
   *
   * @return array<mixed>
   */
  protected function correlation_matrix(array $matrix, string $method): array
  {
    $accepted_methods = ['pearson', 'spearman'];
    if (! in_array(haystack: $accepted_methods, needle: $method)) {
      throw new \InvalidArgumentException("$method is not a supported correlation method.");
    }
    $result = [];
    $columns = count($matrix);
    for ($outer = 0; $outer < $columns; $outer++) {
      for ($inner = 0; $inner < $columns; $inner++) {
        if (isset($result[$inner][$outer])) {
          $result[$outer][$inner] = $result[$inner][$outer];
          continue;
        }
        if ($inner == $outer) {
          $result[$outer][$inner] = 1;
          continue;
        }
        if ($method == 'pearson') {
          $r = math::correlation_pearson($matrix[$outer], $matrix[$inner]);
        } else {
          $r = math::correlation_spearman($matrix[$outer], $matrix[$inner]);
        }
        $result[$outer][$inner] = $r;

        if ($result[$outer][$inner] == null) {
          return [];
        }
      }
    }
    return $result;
  }

  /*
   * Produce a formatted string containing a summary of the DataFrame,
   * including:
   *  - row count
   *  - standard deviation for each column
   *  - average for each column
   *  - minimum value for eachc column
   *  - quantiles for 25%, 50% and 75%
   *  - maximum value for eachc column
   *
   * If any of the columns have a display transformer attached, then
   * they will be formatted accordingly prior to output.
   */
  public function summary(): string
  {
    /** @var DataFrame */
    $sizeDF = $this->size();
    $count = $sizeDF->data();
    $std = $this->std()->data();
    $avg = $this->avg()->data();
    $min = $this->min()->data();
    $max = $this->max()->data();
    $q25 = $this->quantile(0.25)->data();
    $q50 = $this->quantile(0.5)->data();
    $q75 = $this->quantile(0.75)->data();

    $sum = [
      'count' => $count[0],
      'mean' => $avg[0],
      'std' => $std[0],
      'min' => $min[0],
      '25%' => $q25[0],
      '50%' => $q50[0],
      '75%' => $q75[0],
      'max' => $max[0]
    ];
    $sf = $this->clone($sum);

    $data = $sf->data;
    foreach ($this->transformers as $h => $tf) {
      foreach ($data as $index => &$row) {
        if (isset($row[$h])) {
          $row[$h] = $tf($row[$h]);
        } elseif ($this->indexHeader == $h) {
          // apply transformer to the index.
          $data[$tf($index)] = $row;
          unset($data[$index]);
        }
      }
    }

    $str = strings::columnize($data, $sf->headers);

    $shape = $this->shape();
    $str .= "\n" . sprintf("[%s rows x %s columns]", $shape[0], $shape[1]);

    return $str;
  }

  /**
   * Produce a set of seperate DataFrames whereby all rows
   * of the current DataFrame are split by the given column.
   *
   * The result is a GroupedDataFrame, containing all resulting
   * DataFrames within.
   */
  public function groupby(string $column): GroupedDataFrame
  {
    $groups = [];
    $na = [];
    $current = null;
    $currentL = '';
    foreach ($this->data as $index => $row) {
      $cval = $row[$column] ?? null;
      if ($cval !== null) {
        if (! $currentL || $currentL != $cval) {
          if ($current) {
            $groups[$currentL] = $current;
          }
          $current = $groups[$cval] ?? [];
          $currentL = $cval;
        }
        $current[$index] = $row;
      } else {
        $na[$index] = $row;
      }
    }
    if (count($na) > 0) {
      $groups['__NA'] = $na;
    }
    if ($current) {
      $groups[$currentL] = $current;
    }

    $dfs = [];
    foreach ($groups as $key => $items) {
      $dfs[$key] = $this->clone($items);
    }

    return new GroupedDataFrame($dfs, $column);
  }

  /**
   * Remove the specified columns from the DataFrame.
   *
   * -- parameters:
   * @param list<string>|string|null $columns The column(s) to remove.
   * @param $inplace If TRUE then this operation modifies the receiver, otherwise a copy is made.
   *
   * @return DataFrame When $inplace is TRUE the receiver is returned, otherwise a copy is.
   */
  public function drop_columns(array|string|null $columns, bool $inplace = false): DataFrame
  {
    $columns = $this->determineColumns($columns);

    if ($inplace) {
      foreach ($this->data as &$row) {
        foreach ($columns as $column) {
          if (isset($row[$column])) {
            unset($row[$column]);
          }
        }
      }
      $this->data = arrays::compact($this->data);
      $this->headers = array_filter($this->headers, function ($h) use ($columns) {
        return (! arrays::contains($columns, $h));
      });

      foreach ($columns as $column) {
        if (isset($this->transformers[$column]) && $column != $this->indexHeader) {
          unset($this->transformers[$column]);
        }
      }

      return $this;
    }

    $modified = [];
    foreach ($this->data as $index => $row) {
      foreach ($columns as $column) {
        if (isset($row[$column])) {
          unset($row[$column]);
        }
      }
      $modified[$index] = $row;
    }
    $headers = array_filter($this->headers, function ($h) use ($columns) {
      return (! arrays::contains($columns, $h));
    });

    $copy = $this->clone($modified, $headers);
    foreach ($columns as $column) {
      if (isset($copy->transformers[$column]) && $column != $copy->indexHeader) {
        unset($copy->transformers[$column]);
      }
    }
    return $copy;
  }

  /**
   * Remove the rows starting at $start and ending at $end from
   * the DataFrame, where $start and $end represent the relevant
   * row indexes.
   *
   * If $inPlace is TRUE then this operation modifies the
   * current DataFrame, otherwise a copy is returned.
   */
  public function drop_rows(mixed $start, mixed $end = null, bool $inplace = false): DataFrame
  {
    if (is_numeric($start) and is_numeric($end)) {
      if ($inplace) {
        for ($i = $start; $i <= $end; $i++) {
          unset($this->data[$i]);
        }
        return $this;
      } else {
        $modified = $this->data;
        for ($i = $start; $i <= $end; $i++) {
          unset($modified[$i]);
        }
        return $this->clone($modified);
      }
    } else {
      if ($inplace) {
        unset($this->data[$start]);
        return $this;
      } else {
        $modified = $this->data;
        unset($modified[$start]);
        return $this->clone($modified);
      }
    }
  }

  /**
   * Find all duplicate values for a given set of columns, or
   * every column if none are supplied.
   *
   * This method only compares corresponding values between rows
   * of each column, the comparison is only performed vertically.
   *
   * @return list<array<mixed, string|int|float>> All found duplicates.
   */
  public function duplicated(string ...$columns): array
  {
    $columns = $this->determineColumns($columns);

    // generate a hashed string of every row for comparison.
    $hashTable = [];
    foreach ($this->data as $key => $row) {
      $values = [];
      foreach ($columns as $h) {
        $values[] = $row[$h];
      }
      $hashTable[$key] = md5(implode('|', $values));
    }

    $matches = [];
    foreach ($hashTable as $currentK => $currentHash) {
      if (arrays::contains($matches, $currentHash)) {
        continue;
      }

      $matched = [];
      foreach ($hashTable as $key => $hash) {
        if ($key != $currentK && $hash == $currentHash) {
          $matched[] = $key;
        }
      }
      if (count($matched) > 0) {
        $matched = array_merge([$currentK], $matched);
        sort($matched);
        $matches[$currentHash] = $matched;
      }
    }

    return array_values($matches);
  }

  /*
   * Drop all duplicates values within the given columns, or
   * every column if none are supplied.
   *
   * If $inplace is TRUE then this operation is performed on
   * receiver, otherwise a modified copy is returned.
   *
   * See duplicated() for more information.
   */
  public function drop_duplicates(bool $inplace = false, string ...$columns): DataFrame
  {
    $duplicates = $this->duplicated(...$columns);

    if ($inplace) {
      foreach ($duplicates as $indexes) {
        array_shift($indexes); // ignore the first data set (the original).
        for ($i = count($indexes) - 1; $i > -1; $i--) {
          unset($this->data[$indexes[$i]]);
        }
      }
    } else {
      $data = $this->data;
      foreach ($duplicates as $indexes) {
        array_shift($indexes); // ignore the first data set (the original).
        for ($i = count($indexes) - 1; $i > -1; $i--) {
          unset($data[$indexes[$i]]);
        }
      }
      return $this->clone(arrays::compact($data));
    }
    return $this;
  }

  /**
   * Generate a copy of the DataFrame with the columns
   * and row indexes rotated to become the other.
   *
   * This has the effect of grouping common values under
   * a singular index.
   *
   * If a set of columns are provided then all other
   * columns are stripped out of the result.
   *
   * @return DataFrame A new DataFrame with the modified data.
   */
  public function pivot(string ...$columns): DataFrame
  {
    $columns = $this->determineColumns($columns);

    $values = [];
    foreach ($columns as $h) {
      $ticker = 0;
      foreach ($this->data as $index => $row) {
        $newRow = [
          '_index' => $index,
          '_value' => $row[$h] ?? null
        ];
        if (! $ticker) {
          $values[$h] = $newRow;
        } else {
          $values[] = $newRow;
        }
        $ticker++;
      }
    }

    $df = new DataFrame($values);
    $df->display_headers(false);
    $df->display_generic_indexes(false);
    return $df;
  }

  /**
   * The reverse operation of pivot(). Rotate the row indexes and columns back in the other direction.
   *
   * Note that $columns in this method actually refer  to the current grouped indexes that you wish to
   * revert back into actual columns.
   *
   * If no columns are supplied then all indexes are used.
   *
   * @return DataFrame A new DataFrame with the modified data.
   */
  public function depivot(string ...$columns): DataFrame
  {
    if (! arrays::contains($this->headers, '_index') or ! arrays::contains($this->headers, '_value')) {
      throw new \RuntimeException('This dataframe is not a pivot frame and therefore can not be de-pivoted');
    }

    if (count($columns) == 0) {
      $columns = [];
      foreach (array_keys($this->data) as $k) {
        if (! is_int($k)) {
          $columns[] = $k;
        }
      }
    }
    $indexes = array_unique($this->values('_index', false));

    $rows = [];
    foreach ($indexes as $ind) {
      $newRow = [];
      $currentH = '';
      $value = '';
      foreach ($this->data as $h => $row) {
        if (! is_int($h)) {
          $currentH = $h;
        }
        if ($row['_index'] == $ind && arrays::contains($columns, $currentH)) {
          $newRow[$currentH] = $row['_value'];
        }
      }

      $rows[$ind] = $newRow;
    }

    return new DataFrame($rows);
  }

  /**
   * Perform a complex transformation on the DataFrame where
   * by the column specified by $groupColumn becomes the index
   * and all other columns are merged via the merge map.
   *
   * The $mergeMap is an associative array where by each column
   * name specified as a key becomes a column in the resulting
   * DataFrame and each column name specified as a value in the
   * array becomes the corresponding value of that column.
   *
   * -- parameters:
   * @param non-empty-string $groupColumn Used to specify which key in the $array will be used to flatten multiple rows into one.
   * @param array<string, string> $mergeMap Associative (keyed) array specifying pairs of columns that will be merged into header -> value.
   *
   * @return DataFrame A new DataFrame with the transposed data.
   *
   * @see [arrays::transpose](https://github.com/sqonk/phext-core/blob/master/docs/api/arrays.md#transpose) in the PHEXT-Core library for more information.
   */
  public function transpose(string $groupColumn, array $mergeMap): DataFrame
  {
    $this->sort($groupColumn);
    return new DataFrame(arrays::transpose($this->data, $groupColumn, $mergeMap));
  }

  /**
   * Transform the value of one or more columns using the provided
   * callback. If no columns are specified then the operation
   * applies to all.
   *
   * Callback format: `myFunc($value, $columnName, $rowIndex): mixed`
   */
  public function transform(callable $callback, string ...$columns): DataFrame
  {
    $columns = $this->determineColumns($columns);
    foreach ($this->data as $index => &$row) {
      foreach ($columns as $h) {
        if (isset($row[$h])) {
          $row[$h] = $callback($row[$h], $h, $index);
        }
      }
    }
    return $this;
  }


  /**
   * Replace all the values for a column with another set of values.
   *
   * The new value array should hold the exact amount of items as the amount
   * of rows within the DataFrame.
   *
   * -- parameters:
   * @param string $column The column/header that will have its set of values replaced.
   * @param list<string|int|float> $newValues An array of replacement values.
   *
   * @throws \InvalidArgumentException If the specified column is not present.
   * @throws \LengthException If the amount of items in $newValues does not precisely match the amount of rows within the DataFrame.
   */
  public function replace(string $column, array $newValues): self
  {
    if (! arrays::contains($this->headers, $column)) {
      throw new \InvalidArgumentException("Specified column does not exist [$column].");
    }

    [$newCount, $oldCount] = [count($newValues), count($this->values($column, false))];
    if ($oldCount != $newCount) {
      throw new \LengthException("The amount of items in the new value array must be equal to total amount of corresponding values present in the data frame [$newCount vs $oldCount]");
    }

    $newValues = array_combine(array_keys($this->data), $newValues);
    foreach ($this->data as $index => &$row) {
      $row[$column] = $newValues[$index];
    }

    return $this;
  }

  /**
   * Add a new row to the DataFrame. $row is an associative
   * array where the keys should correspond to one or more
   * of the column headers in the DataFrame.
   *
   * $index is an optional keyed index to store the row
   * against. If left empty then the next sequential
   * number shall be used.
   *
   * ** Do not use new or unknown keys not already present
   * in the DataFrame.
   *
   * -- parameters:
   * @param array<string, mixed> $row The new row to add.
   * @param mixed $index An optional custom index to apply to the row.
   *
   * @return self The receiver.
   */
  public function add_row(array $row = [], mixed $index = ''): self
  {
    if (count($this->data) == 0 and ! $this->headers) {
      $this->headers = array_keys($row);
    }

    if ($index !== '') {
      $this->data[$index] = $row;
    } else {
      $this->data[] = $row;
    }

    return $this;
  }

  /**
   * Add a new column to the DataFrame using the provided
   * callback to supply the data. The callback will be called
   * for every row currently in the DataFrame.
   *
   * Callback format: `myFunc($row, $rowIndex)`
   * - $row: associative array containing the value for each column.
   */
  public function add_column(string $column, callable $callback): DataFrame
  {
    foreach ($this->data as $index => &$row) {
      $row[$column] = $callback($row, $index);
    }
    $this->headers[] = $column;
    return $this;
  }

  /**
   * Apply a transformation callback for one or more columns when
   * outputting the DataFrame. If no columns are specified then the
   * operation applies to all.
   *
   * You might use this to format timestamps into dates or to unify
   * the display of currency.
   *
   * The callback should return the formatted value as it should be
   * displayed.
   *
   * This method does not modify the original value within the Dataframe.
   *
   * Callback format: `myFunc($value) -> mixed`
   */
  public function apply_display_transformer(callable $callback, string ...$columns): DataFrame
  {
    $columns = $this->determineColumns($columns);
    foreach ($columns as $column) {
      $this->transformers[$column] = $callback;
    }
    return $this;
  }

  /**
   * Produce a plot object (from the plotlib module) auto-configured
   * to create an image-based graph of one or more columns.
   *
   * -- parameters:
   * @param array<string, mixed> $options represent the chart configuration.
   *  -- title: 		Filename of the chart. Defaults to the chart type and series being plotted.
   *  -- columns: 	Array of the column names to produce charts for.
   *  -- xcolumn: 	A column name to use as the x-axis.
   *  -- one: 		If TRUE then all columns will be rendered onto one chart. When FALSE multiple charts are generated.
   *  -- min:			The minimum Y-value to render.
   *  -- max:			The maximum Y-value to render.
   *  -- lines:		Array of infinite lines to be drawn onto the chart. Each item in the array is an associative array containing the following options:
   *  --- direction:    Either 'v' (Vertical) or 'h' (Horizontal).
   *  --- value:        the numerical position on the respective axis that the line will be rendered.
   *  --- color:        A colour name (e.g. red, blue etc) for the line colour. Default is red.
   *  --- width:        The stroke width of the line, default is 1.
   *  -- labelangle:	  Angular rotation of the x-axis labels, default is 0.
   *  -- bars:		A linear array of values to represent an auxiliary/background bar chart dataset. This will plot on it's own Y axis.
   *  -- barColor:	The colour of the bars dataset, default is 'lightgray'.
   *  -- barWidth:	The width of each bar in the bars dataset, default is 7.
   * @param string $type Represents the type of chart (e.g line, box, bar etc). Possible values:
   *  -- line: 		line chart.
   *  -- linefill: 	line chart with filled area.
   *  -- bar:			bar chart.
   *  -- barstacked:	bar chart with each series stacked atop for each data point.
   *  -- scatter:		scatter chart.
   *  -- box:			Similar to a stock plot but with a fifth median value.
   *
   * @return BulkPlot An object containing the plots to be rendered.
   * @see plotlib for possibly more information.
   */
  public function plot(string $type, array $options = []): BulkPlot
  {
    $columns = $this->determineColumns(arrays::safe_value($options, 'columns'));
    $title = arrays::safe_value($options, 'title', '');
    $xcolumn = arrays::safe_value($options, 'xcolumn', null);
    $oneChart = arrays::safe_value($options, 'one', false);

    $plot = new BulkPlot($title);
    $all_series = [];

    $xseries = null;
    $xtr = null;
    if ($xcolumn) {
      foreach ($this->headers as $h) {
        if ($h == $xcolumn) {
          $xseries = $this->values($h);
          break;
        }
      }
      $xtr = $this->transformers[$xcolumn] ?? null;
    } else {
      $xseries = array_keys($this->data);
      $xtr = $this->transformers[$this->indexHeader] ?? null;
    }

    foreach ($columns as $i => $h) {
      if ($xcolumn === null or ($xcolumn !== null && $h != $xcolumn)) {
        $values = $this->values($h);
        $ytr = $this->transformers[$h] ?? null;

        if ($oneChart) {
          $all_series[] = $values;
        } else {
          $plot->add($type, [$values], array_merge($options, [
            'xseries' => $xseries,
            'xformatter' => $xtr,
            'legend' => $h,
            'yformatter' => $ytr
          ]));
        }
      }
    }

    if ($oneChart) {
      $plot->add($type, $all_series, array_merge($options, [
        'xseries' => $xseries,
        'xformatter' => $xtr,
        'legend' => $columns
      ]));
    }


    return $plot;
  }

  /**
   * Produce a candle-stick chart, typically used for tracking stock prices.
   *
   * You must specify exactly 4 columns.
   *
   * $options can include a 'volume' key, specifying an associative array with
   * the subkeys 'key', 'color' and 'width' for representing volume as a
   * background bar chart.
   *
   * All other standard option keys can be passed in.
   *
   * -- parameters:
   * @param string $openP The column used for the opening price values.
   * @param string $closeP The column used for the closing price values.
   * @param string $lowP The column used for the low price values.
   * @param string $highP The column used for the high price values.
   * @param array<string, mixed> $options
   *
   * @return BulkPlot The set of plots to be rendered.
   */
  public function stock(string $openP, string $closeP, string $lowP, string $highP, array $options = []): BulkPlot
  {
    $series = [$this->_sequence($openP, $closeP, $lowP, $highP)];

    $title = arrays::safe_value($options, 'title');
    $xcolumn = arrays::safe_value($options, 'xcolumn', null);
    if (isset($options['volume'])) {
      $options['bars'] = $this->values($options['volume']['key'], false);
      if (isset($options['volume']['color'])) {
        $options['barColor'] = $options['volume']['color'];
      }
      if (isset($options['volume']['width'])) {
        $options['barWidth'] = $options['volume']['width'];
      }
      if (isset($options['volume']['legend'])) {
        $options['barLegend'] = $options['volume']['legend'];
      }
    }

    if ($xcolumn) {
      $xseries = $this->values($xcolumn);
      $xtr = $this->transformers[$xcolumn] ?? null;
    } else {
      $xseries = array_keys($this->data);
      $xtr = $this->transformers[$this->indexHeader] ?? null;
    }

    $plot = new BulkPlot($title);
    $plot->add('stock', $series, array_merge($options, [
      'xseries' => $xseries,
      'xformatter' => $xtr
    ]));

    return $plot;
  }

  /**
   * @internal
   *
   * @return list<mixed>
   */
  private function _sequence(string ...$columns): array
  {
    $columns = $this->determineColumns($columns);
    $out = [];

    foreach ($this as $i => $row) {
      foreach ($columns as $col) {
        $out[] = $row[$col] ?? null;
      }
    }

    return $out;
  }

  /**
   * Create a box plot chart, which is a singular data point of box-like
   * appearance that illustrates the place of the 25%, 50% and 75% quantiles
   * as well as the outer whiskers.
   *
   * @return BulkPlot An object containing the plots to be rendered.
   */
  public function box(string ...$columns): BulkPlot
  {
    $columns = $this->determineColumns($columns);
    $plot = new BulkPlot('box');

    foreach ($columns as $h) {
      $q25 = $this->quantile(0.25, $h);
      $q50 = $this->quantile(0.50, $h);
      $q75 = $this->quantile(0.75, $h);
      $whisker = ($q75 - $q25) * 1.5;
      $series = [$q25, $q75, $q25 - $whisker, $q75 + $whisker, $q50];

      $plot->add('box', [$series], [
        'legend' => $h,
        'hideAllTicks' => true,
        'font' => FF_FONT1
      ]);
    }

    return $plot;
  }

  /**
   * Create a bar chart styled in the fashion of a histogram.
   *
   * -- parameters:
   * @param array<string, mixed> $options is an array containing the following:
   *  -- columns: Array of column names to use (1 or more)
   *  -- bins: Number of bins to use for the histogram. Defaults to 10.
   *  -- cumulative: Create a stacked histogram showing the accumulative scale along with the main. Defaults to FALSE.
   *  -- title: Displayed title of the histogram.
   *  -- low: Low range bins filter. Defaults to NULL.
   *  -- high: High range bins filter. Defaults to NULL.
   *
   * @return BulkPlot An object containing the plots to be rendered.
   */
  public function hist(array $options = []): BulkPlot
  {
    $columns = $this->determineColumns(arrays::safe_value($options, 'columns'));
    $bins = $options['bins'] ?? 10;
    $dlow = $options['low'] ?? null;
    $dhigh = $options['high'] ?? null;
    $is_cumulative = $options['cumulative'] ?? false;
    $title = $options['title'] ?? 'hist';

    $b_array = null;
    if (is_array($bins)) {
      $b_array = $bins;
      $bins = count($b_array) - 1;
    }

    $plot = new BulkPlot($title);

    foreach ($columns as $h) {
      $values = $this->values($h);
      $min = ($dlow !== null) ? $dlow : floor((float)min($values));
      $max = ($dhigh !== null) ? $dhigh : ceil((float)max($values));
      $delta = ($max - $min) / $bins;

      $processed = [];
      $dist = [];
      $dist2 = [];
      $cumm = 0;
      for ($i = 0; $i < $bins; $i++) {
        $lowBin = $b_array ? $b_array[$i] : $min + $i * $delta;
        $hiBin = $b_array ? $b_array[$i + 1] : $lowBin + $delta;
        $tally = 0;
        $vcount = count($values);

        for ($j = 0; $j < $vcount; $j++) {
          if (arrays::contains($processed, $j)) {
            continue;
          }

          if ($j == $vcount - 1) {
            $inRange = ($values[$j] >= $lowBin && $values[$j] <= $hiBin);
          } else {
            $inRange = ($values[$j] >= $lowBin && $values[$j] < $hiBin);
          }

          if ($inRange) {
            $tally++;
            $processed[] = $j;

            if ($is_cumulative) {
              $cumm++;
            }
          }
        }
        $dist[] = $tally;
        if ($is_cumulative) {
          $dist2[] = $cumm - $tally;
        }
      }
      if ($is_cumulative) {
        $plot->add('barstacked', [$dist, $dist2], [
          'legend' => [$h, 'accumulated'],
          'matchBorder' => true,
          'width' => 1.0,
          'font' => FF_FONT1
        ]);
      } else {
        $plot->add('bar', [$dist], [
          'legend' => $h,
          'matchBorder' => true,
          'width' => 1.0,
          'font' => FF_FONT1
        ]);
      }
    }

    return $plot;
  }

  /**
   * Export the DataFrame to a delimited text file (CSV).
   *
   * -- parameters:
   * @param string $filePath: The destination file.
   * @param ?list<string> $columns: The columns to export, or all if null is supplied.
   * @param string $delimiter: The character that separates each column.
   * @param bool $includeIndex: When TRUE, adds the data frame row index as the first column.
   */
  public function export(string $filePath, ?array $columns = null, string $delimiter = ',', bool $includeIndex = true): void
  {
    $columns = $this->determineColumns($columns);

    try {
      $fh = fopen($filePath, 'w+');
      if ($this->showHeaders) {
        $start = [];
        if ($includeIndex) {
          $start[] = 'index';
        }
        $headers = array_merge($start, $columns);
        fputcsv($fh, $headers, $delimiter);
      }

      foreach ($this->data as $index => $row) {
        $index = ($this->showGenericIndexes || ! is_int($index)) ? $index : '';
        if ($index && $this->indexHeader && isset($this->transformers[$this->indexHeader])) {
          $tr = $this->transformers[$this->indexHeader];
          $index = $tr($index);
        }
        $out = [];
        if ($includeIndex) {
          $out[] = $index;
        }
        foreach ($columns as $h) {
          $value = $row[$h] ?? '';
          if ($value && isset($this->transformers[$h])) {
            $tr = $this->transformers[$h];
            $value = $tr($value);
          }
          $out[] = $value;
        }

        fputcsv($fh, $out, $delimiter);
      }
    } finally {
      if (isset($fh) && is_resource($fh)) {
        fclose($fh);
      }
    }
  }
}
