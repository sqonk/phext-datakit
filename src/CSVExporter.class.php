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
 * @license		MIT see license.txt
 * @copyright	2019 Sqonk Pty Ltd.
 *
 *
 * This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

use sqonk\phext\core\arrays;
use sqonk\phext\core\strings;

/**
 * The CSVExporter class can be used for producing CSV documents. It abstracts the mechanics of
 * producing the file format, allowing your code to focus on its own logic.
 *
 * Under the hood it relies on `fputcsv` for outputting rows.
 *
 * It is designed for exporting data as productively as possible, the system for which
 * follows a configure-then-execute pattern. By setting up a field map, which creates a
 * relationship between the human-readable column headers and the array keys for eventual
 * data that is provided to it, all rows can be passed in at once or as required.
 *
 * This class works in real-time, meaning that the data is written out to the stream as
 * you pass it in. It is also stringable, allowing it to be used in many standard forms
 * of output that can work with strings.
 */
class CSVExporter
{
  /**
   * @var list<string>
   */
  protected array $headers = [];

  /**
   * @var array<string, string>
   */
  protected array $field_map = [];

  protected string $path;

  /**
   * @var ?resource
   */
  protected $fh;
  protected bool $headersWritten = false;


  /**
   * Create a new CSV Exporter.
   *
   * -- parameters:
   * @param $path A path to the output file that will be used to generate the CSV. If set to `NULL` the CSV will be produced directly in memory. Defaults to `NULL`.
   */
  public function __construct(string $path = '')
  {
    if (!$path) {
      $path = 'php://memory';
    }
    $this->path = $path;
  }

  public function __destruct()
  {
    $this->closeFH();
    if (file_exists($this->path)) {
      unlink($this->path);
    }
  }

  protected function closeFH(): void
  {
    if ($this->fh) {
      fflush($this->fh);
      fclose($this->fh);
      $this->fh = null;
    }
  }

  /**
   * @return resource The file handle.
   */
  protected function fh()
  {
    if (!$this->fh) {
      $this->fh = fopen($this->path, 'w');
      if (!$this->headersWritten) {
        $this->writeHeaders();
      }
    }
    return $this->fh;
  }

  protected function writeHeaders(): void
  {
    $fh = $this->fh;
    fputcsv($fh, $this->headers);
    $this->headersWritten = true;
  }

  /**
   * Return the current header-to-key map.
   *
   * @return array<string, string> A keyed array where the keys are the column headers and the values are the corresponding value keys.
   */
  public function map(): array
  {
    return $this->field_map;
  }

  /**
   * Set a map for the exporter, which is series of column headers and array keys
   * that will be used to automatically build the CSV from one or more objects or
   * associative arrays passed into the class at a later stage.
   *
   * Will trigger a warning if called after the headers have already been output.
   *
   * -- parameters:
   * @param array<string, string> $fieldMap An associative array where the column headers are the array keys and
   * the values are the array values.
   *
   * @return bool TRUE if the field map was successfully set, FALSE otherwise.
   */
  public function set_map(array $fieldMap): bool
  {
    if ($this->headersWritten) {
      trigger_error('Can not set field map after headers have already been output.', E_USER_WARNING);
      return false;
    }
    $this->field_map = $fieldMap;
    $this->headers = array_keys($fieldMap);

    return true;
  }

  /**
   * Map a column header to a set array key that will be used to acquire the corresponding
   * value from each record.
   *
   * -- parameters:
   * @param string $header The column header.
   * @param string $key The corresponding key for accessing the value within a record.
   *
   * Will trigger a warning if called after the headers have already been output.
   *
   * @return bool TRUE if the map header pair were successfully set, FALSE otherwise.
   */
  public function add_map_pair(string $header, string $key): bool
  {
    if ($this->headersWritten) {
      trigger_error('Can not set field map after headers have already been output.', E_USER_WARNING);
      return false;
    }
    $this->field_map[$header] = $key;
    $this->headers[] = $header;

    return true;
  }

  /**
   * Return the current set of human-readable column headers.
   *
   * @return list<string> The column headers.
   */
  public function headers(): array
  {
    return $this->headers;
  }

  /**
   * Set the column headers for the exporter.
   *
   * *NOTE:* If you have previously set a map by calling `set_map()` then the headers are
   * automatically extrapolated from it. You do not need to call this method unless
   * you are bypassing the use of field maps and records.
   *
   * -- parameters:
   * @param list<string> $headers A sequential array of strings representing the column headers.
   *
   * Will trigger a warning if called after the headers have already been output.
   * Will also trigger a notice if a field map has previously been set.
   *
   * @return bool TRUE if the headers were successfully set, FALSE otherwise.
   */
  public function set_headers(array $headers): bool
  {
    if ($this->headersWritten) {
      trigger_error('Can not set field map after headers have already been output.', E_USER_WARNING);
      return false;
    }

    $this->headers = $headers;

    if (count($this->field_map) > 0) {
      trigger_error("## WARNING: subsequent call to set_headers after the field map was set. This may upset your column order if it is not intentional.", E_USER_NOTICE);
    }
    return true;
  }

  /**
   * Add a series of values as the next row in the CSV.
   *
   * -- parameters:
   * @param array<mixed>$row A sequential array of the values corresponding the order of the column headers.
   *
   * @return self The CSV object.
   */
  public function add_raw_row(array $row): self
  {
    fputcsv($this->fh(), $row);

    return $this;
  }

  /**
   * Add a single record to the CSV. This method differs from `add_raw_row()` in that the provided array or object
   * should be associative where the keys correspond to the column headers.
   *
   * -- parameters:
   * @param mixed $record An associative array or object containing the row of data.
   *
   * @throws \RuntimeException If no field map has been set.
   * @throws \InvalidArgumentException If the provided record is not of the correct type.
   *
   * @return self The CSV object.
   */
  public function add_record(mixed $record): self
  {
    if (!is_array($record) and !$record instanceof \ArrayAccess) {
      throw new \InvalidArgumentException('Record must be either an array or an object that implements ArrayAccess');
    }
    if (count($this->field_map) == 0) {
      throw new \RuntimeException('Tried to add record before the field map was provided.');
    }

    $keys = array_values($this->field_map);
    $row = [];
    foreach ($keys as $k) {
      $row[] = $record[$k] ?? '';
    }

    $this->add_raw_row($row);

    return $this;
  }

  /**
   * Add multiple records to the CSV.
   *
   * -- parameters:
   * @param mixed $records The array of records to add.
   *
   * @throws \InvalidArgumentException If $records is not of the correct type.
   *
   * @see add_record() for other possible exceptions that may be thrown.
   *
   * @return self The CSV object.
   */
  public function add_records(mixed $records): self
  {
    if (!is_array($records) and !$records instanceof \ArrayAccess) {
      throw new \InvalidArgumentException('Record must be either an array or an object that implements ArrayAccess');
    }

    foreach ($records as $r) {
      $this->add_record($r);
    }

    return $this;
  }

  /**
   * Convert the CSV in its current state to a string.
   */
  public function __tostring(): string
  {
    $pos = ftell($this->fh());
    if ($pos == 0) {
      return '';
    }

    rewind($this->fh());
    return trim(fread($this->fh(), $pos));
  }
}

/**
 * @deprecated The CSV class has been renamed to CSVExporter, please update your code accordingly.
 */
class CSV extends CSVExporter
{
  public function __construct(?string $path = null)
  {
    parent::__construct($path);
    trigger_error('The CSV class has been renamed to CSVExporter, please update your code accordingly.', E_USER_NOTICE);
  }
}
