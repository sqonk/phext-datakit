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

/**
 * The CSVImporter is designed to efficiently load or parse CSV documents. It is the 
 * underlying engine used by the static methods in the Importer class.
 */
class CSVImporter implements \Iterator
{
    protected bool $headersAreFirstRow = false;
    protected ?array $headers = null;
    protected $handle; // Either the file handle or array of rows (extrapolated from the raw string).
    protected string $delim = ","; 
    protected string $lineEnding = "\n"; // used for raw string input.
    protected string $enclosure = "\"";
    protected bool $initialised = false; // Set on scan of first row.
    protected int $arrayIndex = 0;
    protected int $skipRows = 0;
    protected int $rowCount = 0;
    protected array|bool|null $current = null;
    protected bool $skipRowsHeaderAdjustmentMade = false;
    
    /**
     * Initialise a new CSVImporter. Use this static method if you wish to chain a sequence of calls in one line.
     * 
     * -- parameters:
     * @param $input Either the file path of the CSV Document or the raw CSV text. @see $inputIsRawData.
     * @param $inputIsRawData When `TRUE`, the `$input` parameter is interpreted as containing the CSV data. When FALSE it is assumed to be the file path to the relevant CSV document.
     * @param $headersAreFirstRow When TRUE the first row of the CSV document is assigned as the headers, which are the resulting keys in the associative array produced for each row that is read in. Defaults to `FALSE`.
     * @param $customHeaders Assigns the given array as the headers for the import, which are the resulting keys in the associative array produced for each row that is read in. If this is set and $headersAreFirstRow is set to `TRUE` then the custom headers will override it, however the first row will still be skipped over.
     * @param $skipRows Additionally skip over the given number or rows before reading begins.
     * @param $delimiter Set the field delimiter (one single-byte character only).
     * @param $enclosedBy Set the field enclosure character (one single-byte character only).
     * @param $lineEnding Set character sequence that denotes the end of a line (row).
     */
    static public function init(string $input, bool $inputIsRawData = false, bool $headersAreFirstRow = false, ?array $customHeaders = null, int $skipRows = 0, string $delimiter = ",", string $enclosedBy = "\"", string $lineEnding = "\n"): CSVImporter {
        return new CSVImporter(
            $input, 
            $inputIsRawData, 
            $headersAreFirstRow, 
            $customHeaders, 
            $skipRows, 
            $delimiter, 
            $enclosedBy, 
            $lineEnding
        );
    }
    
    /**
     * Initialise a new CSVImporter.
     * 
     * -- parameters:
     * @param $input Either the file path of the CSV Document or the raw CSV text. @see $inputIsRawData.
     * @param $inputIsRawData When `TRUE`, the `$input` parameter is interpreted as containing the CSV data. When FALSE it is assumed to be the file path to the relevant CSV document.
     * @param $headersAreFirstRow When TRUE the first row of the CSV document is assigned as the headers, which are the resulting keys in the associative array produced for each row that is read in. Defaults to `FALSE`.
     * @param $customHeaders Assigns the given array as the headers for the import, which are the resulting keys in the associative array produced for each row that is read in. If this is set and $headersAreFirstRow is set to `TRUE` then the custom headers will override it, however the first row will still be skipped over.
     * @param $skipRows Additionally skip over the given number or rows before reading begins.
     * @param $delimiter Set the field delimiter (one single-byte character only).
     * @param $enclosedBy Set the field enclosure character (one single-byte character only).
     * @param $lineEnding Set character sequence that denotes the end of a line (row).
     */
    public function __construct(private string $input, private bool $inputIsRawData = false, bool $headersAreFirstRow = false, ?array $customHeaders = null, int $skipRows = 0, string $delimiter = ",", string $enclosedBy = "\"", string $lineEnding = "\n")
    {
        if (! $this->inputIsRawData) {
    		if (! $this->handle = @fopen($this->input, 'r'))
    			throw new \RuntimeException("[{$this->input}] could not be opened, empty handle returned.");
            
			@flock($this->handle, LOCK_SH);
        }
        
        if ($skipRows)
            $this->skip($skipRows);
        if ($customHeaders)
            $this->custom_headers($customHeaders);
        $this->headers_first_row($headersAreFirstRow);
        $this->delimiter($delimiter);
        $this->enclosedBy($enclosedBy);
        $this->line_ending($lineEnding);
    }
    
    public function __destruct()
    {
        $this->close();
    }
    
    /**
     * Close off access to the underlying file resource, if one is open. Repeated calls to this,
     * or if the input source is a raw string, will do nothing.
     */
    public function close(): void 
    {
        if (is_resource($this->handle)) {
			@flock($this->handle, LOCK_UN);
			@fclose($this->handle);
        }
        $this->handle = null;
    }
    
    /**
     * Set the field delimiter (one single-byte character only).
     */
    public function delimiter(string $seperatedBy): CSVImporter 
    {
        $this->delim = $seperatedBy;
        return $this;
    }
    
    /**
     * Set the field enclosure character (one single-byte character only).
     */
    public function enclosedBy(string $enclosure): CSVImporter 
    {
        $this->enclosure = $enclosure;
        return $this;
    }
    
    /**
     * Set character sequence that denotes the end of a line (row).
     */
    public function line_ending(string $delimiter): CSVImporter 
    {
        $this->lineEnding = $delimiter;
        return $this;
    }
    
    /**
     * When `TRUE`, the `$input` parameter is interpreted as containing the CSV data. When FALSE 
     * it is assumed to be the file path to the relevant CSV document.
     */
    public function headers_first_row(bool $headersPresent): CSVImporter 
    {
        $this->headersAreFirstRow = $headersPresent;
        return $this;
    }
    
    /**
     * Additionally skip over the given number or rows before reading begins. This method 
     * has no effect once reading has begun, unless the importer is reset.
     * 
     * If the given value exceeds the number of rows in the CSV then the importer will raise an E_USER_WARNING at the time of internal initialisation.
     * 
     * @see reset
     */
    public function skip(int $rows): CSVImporter 
    {
        $this->skipRows = $rows;
        return $this;
    }
    
    /**
     * Assigns the given array as the headers for the import, which are the resulting keys in 
     * the associative array produced for each row that is read in. If this is set and 
     * $headersAreFirstRow is set to `TRUE` then the custom headers will override it, however 
     * the first row will still be skipped over.
     */
    public function custom_headers(array $headers): CSVImporter 
    {
        $this->headers = $headers;
        return $this;
    }
    
    protected function initSource(): bool 
    {
        // If headers are in first row but custom header have been set, then simply skip.
        if (! $this->skipRowsHeaderAdjustmentMade && $this->headersAreFirstRow && $this->headers) {
            $this->skipRows++;
            $this->skipRowsHeaderAdjustmentMade = true;
        }
        
        if ($this->inputIsRawData) 
        {
            $this->handle = explode($this->lineEnding, trim($this->input));
            $count = count($this->handle);
            if ($count == 0 || ($count == 1 && $this->handle[0] === '')) {
                trigger_error('Provided CSV data is empty.', E_USER_NOTICE);
                return false;
            }
            else if ($this->skipRows > $count-1) {
                trigger_error('Attempt to skip past beyond the last row of the CSV.', E_USER_WARNING);
                return false;
            }
        }
        
        if ($this->headersAreFirstRow && ! $this->headers) {
            $r = $this->advance();
            if ($r === false)
                return false;
            $this->headers = $r;
        }
        
        if ($this->inputIsRawData) {
            $this->arrayIndex += $this->skipRows;
            $this->rowCount = $this->arrayIndex;
        }
        else if ($this->skipRows > 0) 
        {
			foreach (sequence(0, $this->skipRows-1) as $i) {
			    $r = fgets($this->handle);
                if ($r === false) {
                    trigger_error('Attempt to skip past beyond the last row of the CSV.', E_USER_WARNING);
                    return false;
                }
                $this->rowCount++;
			}
        }

        $this->initialised = true;
        return true;
    }
    
    protected function advance(): array|bool 
    {
        if ($this->inputIsRawData) 
        {
            if ($this->arrayIndex >= count($this->handle))
                return false;
            $h = str_getcsv(
                string:$this->handle[$this->arrayIndex], 
                enclosure:$this->enclosure, 
                separator:$this->delim
            );
            $this->arrayIndex++;
        }
        else {
            $h = fgetcsv(stream:$this->handle, enclosure:$this->enclosure, separator:$this->delim);
        }
        $this->rowCount++;
        
        return $h;
    }
    
    /**
     * Advance the importer by one line and return the resulting row of fields.
     * 
     * @return An associative array containing the decoded fields that were read in or FALSE if the end of the CSV was reached.
     */
    public function next_row(): array|bool {
        $this->next();
        return $this->current;
    }
    
    /**
     * Preflight the importer by running the initial internal setup and verifying that it completed without error.
     * 
     * @return `TRUE` if no problems were encountered, `FALSE` otherwise.
     */
    public function validate(): bool {
        return $this->initialised || $this->initSource();
    }
    
    /**
     * Reset the importer so the next reads starts from the top of the file. All steps, including internal initialisation are taken again.
     */
    public function reset(): void 
    {
        if (! $this->inputIsRawData && is_resource($this->handle)) {
            rewind($this->handle);
        }
        $this->initialised = false;
        $this->arrayIndex = 0;
        $this->rowCount = 0;
        $this->current = null;
    }
    
    /**
     * Return the calculated set of headers.
     */
    public function headers(): ?array {
        return $this->validate() ? $this->headers : null;
    }
    
    /**
     * Return all remaining rows yet to be read in from the document.
     * 
     * @return An array containing every row that was read in.
     */
    public function all_remaining(): array 
    {
        $out = [];
        while ($row = $this->next_row())
            $out[] = $row;
        return $out;
    }
    
    /**
     * Return all rows contained within the CSV document. If reading has already commenced then
     * the importer is first reset.
     * 
     * @return An array containing every row that was read in.
     */
    public function all(): array 
    {
        if ($this->rowCount > 0)
            $this->reset();
        return $this->all_remaining();
    }
    
    /**
     * The index correlating to the position in the CSV document the importer is currently up to. It 
     * represents the total amount of lines that have been read in.
     * 
     * The initial index is 0.
     * 
     * This directly relates in a 1:1 fashion with the original CSV document. i.e. if the headers are 
     * in the first row and 1 row been read then the row index will be at 2.
     */
    public function row_index(): int {
        return $this->rowCount;
    }
        
    // ----- Iterator
    
    /**
     * Iterator implementation method. Advances the importer to the next line, reading in
     * the next row.
     */
    public function next(): void
    {
        if (! $this->initialised) {
            if (! $this->initSource())
                return;
        }
        
        while (true) {
            $row = $this->advance();
            
            if (! $row || (count($row) > 0 && $row[0] !== null))
                break;
        }
        
        if ($row && $this->headers) {
            $out = [];
            $hcount = count($this->headers);
            foreach (range(0, count($row)-1) as $i) {
                $h = ($i < $hcount) ? $this->headers[$i] : $i;
                $out[$h] = $row[$i];
            }
			$row = $out;
        }
        
        $this->current = $row;
    }
    
    /**
     * Iterator implementation method. Returns the current row. Will advance the reader by one 
     * line if reading has not yet begun.
     */
    public function current(): mixed {
        if ($this->rowCount == 0)
            $this->next();
        return $this->current;
    }
    
    /**
     * Iterator implementation method. Returns the current row count.
     */
    public function key(): mixed {
        return $this->rowCount;
    }
    
    /**
     * Iterator implementation method. Calls reset().
     */
    public function rewind(): void {
        $this->reset();
    }
    
    /**
     * Iterator implementation method.
     */
    public function valid(): bool {
        return $this->rowCount == 0 || $this->current;
    }
}