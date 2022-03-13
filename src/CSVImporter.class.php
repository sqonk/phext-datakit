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
    
    public function delimiter(string $seperatedBy): CSVImporter 
    {
        $this->delim = $seperatedBy;
        return $this;
    }
    
    public function enclosedBy(string $enclosure): CSVImporter 
    {
        $this->enclosure = $enclosure;
        return $this;
    }
    
    public function line_ending(string $delimiter): CSVImporter 
    {
        $this->lineEnding = $delimiter;
        return $this;
    }
    
    public function headers_first_row(bool $headersPresent): CSVImporter 
    {
        $this->headersAreFirstRow = $headersPresent;
        return $this;
    }
    
    public function skip(int $rows): CSVImporter 
    {
        $this->skipRows = $rows;
        return $this;
    }
    
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
        }
        else if ($this->skipRows > 0) 
        {
			foreach (sequence(0, $this->skipRows-1) as $i) {
			    $r = fgets($this->handle);
                if ($r === false) {
                    trigger_error('Attempt to skip past beyond the last row of the CSV.', E_USER_WARNING);
                    return false;
                }
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
    
    public function next_row(): array|bool {
        $this->next();
        return $this->current;
    }
    
    public function validate(): bool {
        return $this->initialised || $this->initSource();
    }
    
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
    
    public function headers(): ?array {
        return $this->validate() ? $this->headers : null;
    }
    
    public function all_remaining(): array 
    {
        $out = [];
        while ($row = $this->next_row())
            $out[] = $row;
        return $out;
    }
    
    public function all(): array 
    {
        if ($this->rowCount > 0)
            $this->reset();
        return $this->all_remaining();
    }
    
    public function row_index(): int {
        return $this->rowCount;
    }
        
    // ----- Iterator
    
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
    
    public function current(): mixed {
        if ($this->rowCount == 0)
            $this->next();
        return $this->current;
    }
    
    public function key(): mixed {
        return $this->rowCount;
    }
    
    public function rewind(): void {
        $this->reset();
    }
    
    public function valid(): bool {
        return $this->rowCount == 0 || $this->current;
    }
}