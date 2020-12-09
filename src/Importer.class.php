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
 * A selection of routines for importing data from various static sources such as files.
*/
class Importer
{
    /**
     * Import a CSV from a string containing the data.
     * 
     * Your callback method should be in the format of:
     * 
     * `function myCSVCallback($row)`
     * 
     * where $row is an array of the values retrieved from the current row in the CSV. When the
     * first row is indicated as containing the column headers then the supplied array will
     * be indexed with the column headers as the keys.
     * 
     * In the cases were the CSV has no column headers then the supplied array will be in simple
     * sequential order.
     * 
     * -- parameters:
     * @param $callback A callback method to process each row. Pass in NULL to have the data returned at the end.
     * @param $data The CSV data in string format.
     * @param $headersAreFirstRow TRUE or FALSE, where are not the first row contains headers.
     * @param $customHeaders A custom set of column headers to override any existing or absent headers.
     * 
     * @return TRUE upon successful completion or the imported data array when no callback is
     * being used. FALSE on failure to process the data source.
     * 
     * This method will generate a user level warning if data is empty or can not otherwise be derived into
     * at least 1 line of applicable data.
     */
    static public function csv_data(?callable $callback, string $data, bool $headersAreFirstRow = false, ?array $customHeaders = null)
    {
        $lines = explode("\n", trim($data));
        $count = count($lines);
        
        if ($count == 0 or ($count == 1 && $lines[0] === '')) {
            trigger_error('Provided CSV data is empty.', E_USER_NOTICE);
            return $callback ? false : [];
        }
        
        $imported = ($callback) ? null : [];
        
        if ($headersAreFirstRow || is_array($customHeaders))
        {
            $headers = null;
            if ($headersAreFirstRow) {
                $headers = str_getcsv($lines[0]);
                $start = 1;
            }
            else {
                $start = 0;
            }
            if (is_array($customHeaders))
                $headers = $customHeaders;
        
            for ($i = $start; $i < count($lines); $i++)
            {
                $row = str_getcsv($lines[$i]);
                
                if (count($row) == 0 or $row[0] === null)
                    continue; // ignore blank lines.
                
                $out = [];
                for ($j = 0; $j < count($row); $j++) {
                    $h = ($j < count($headers)) ? $headers[$j] : $j;
                    $out[$h] = $row[$j];
                }
            
                if ($callback)
                    $callback($out);
                else
                    $imported[] = $out;
            }    
        }
        else
        {
            for ($i = 0; $i < count($lines); $i++) 
            {
                $row = str_getcsv($lines[$i]);
                
                if (count($row) == 0 or $row[0] === null)
                    continue; // ignore blank lines.   
                
                if ($callback)         
                    $callback($row);
                else
                    $imported[] = $row;
            }
        }
        
        return is_array($imported) ? $imported : true;
    }
    
    /**
     * Import a CSV from a local file on disk or a URL.
     * 
     * Provides a fast and convienient way of importing data from CSV formats. Each row
     * is returned to your callback method as an array of values, where you may do
     * as you desire with it. Alternatively if you pass in NULL as the callback then
     * all the data will be returned as an array.
     * 
     * Your callback method should be in the format of:
     * 
     * `function myCSVCallback($row)`
     * 
     * where $row is an array of the values retrieved from the current row in the CSV. When the
     * first row is indicated as containing the column headers then the supplied array will
     * be indexed with the column headers as the keys.
     * 
     * In the cases were the CSV has no column headers then the supplied array will be in simple
     * sequential order.
     * 
     * -- parameters:
     * @param $callback A callback method to process each row. Pass in NULL to have the data returned at the end.
     * @param $filePath Path or URL to the file.
     * @param $headersAreFirstRow TRUE or FALSE, where are not the first row contains headers.
     * @param $customHeaders A custom set of column headers to override any existing or absent headers.
     * @param $skipRows	Skip over a specified number of rows at the start. Defaults to 0.
     * 
     * @return TRUE upon successful completion or the imported data array when no callback is being used.
     * 
     * This method will throw a `RuntimeException` if the file can not be opened for any reason.
     */
    static public function csv_file(?callable $callback, string $filePath, bool $headersAreFirstRow = false, ?array $customHeaders = null, int $skipRows = 0)
    {
        $data = ($callback) ? null : [];
        
        try
        {
    		if (! $fh = @fopen($filePath, 'r'))
    			throw new \RuntimeException("[{$filePath}] could not be opened, empty handle returned.");
		
    		@flock($fh, LOCK_SH);
            
    		// Skip over a specified number of rows at the start. Defaults to 0.
    		if ($skipRows > 0) {
    			foreach (sequence(0, $skipRows-1) as $i)
    				fgets($fh); 
    		}
		
            $headers = null;
            if ($headersAreFirstRow)
                $headers = fgetcsv($fh);
            if (is_array($customHeaders))
                $headers = $customHeaders; // custom header override.
        
            while (($row = fgetcsv($fh)) !== false)
            {
                if (count($row) == 0 or $row[0] === null)
                    continue; // ignore blank lines.
            
    			if ($headers)
    			{
    	            $out = [];
    	            for ($i = 0; $i < count($row); $i++) {
    	                $h = ($i < count($headers)) ? $headers[$i] : $i;
    	                $out[$h] = $row[$i];
    	            }
    				$row = $out;
    			}
            
                if ($callback)
                    $callback($row);
                else
                    $data[] = $row;
            }    
        }
        finally {
            if (isset($fh) && is_resource($fh)) {
    			@flock($fh, LOCK_UN);
    			@fclose($fh);
            }
        }

        return is_array($data) ? $data : true;
    }
    
    /**
     * Import a CSV from a local file on disk or a URL and yield one row at a time
     * as a generator to an outer loop.
     * 
     * Each yielded row is an array of the values retrieved from the current row in
     * the CSV. When the first row is indicated as containing the column headers then
     * the supplied array will be indexed with the column headers as the keys.
     * 
     * In the cases were the CSV has no column headers then the supplied array will be in simple
     * sequential order.
     * 
     * -- parameters:
     * @param $filePath Path or URL to the file.
     * @param $headersAreFirstRow TRUE or FALSE, where are not the first row contains headers.
     * @param $customHeaders A custom set of column headers to override any existing or absent headers.
     * @param $skipRows	Skip over a specified number of rows at the start. Defaults to 0.
     * 
     * @return A generator for use in a foreach loop.
     * 
     * This method will throw a `RuntimeException` if the file can not be opened for any reason.
     */
    static public function yield_csv(string $filePath, bool $headersAreFirstRow = false, ?array $customHeaders = null, int $skipRows = 0)
    {
        try
        {
    		if (! $fh = @fopen($filePath, 'r'))
    			throw new \RuntimeException("[{$filePath}] could not be opened, empty handle returned.");
            @flock($fh, LOCK_SH);
            
            // Skip over a specified number of rows at the start. Defaults to 0.
    		if ($skipRows > 0) 
    			foreach (sequence(0, $skipRows-1) as $i)
    				fgets($fh); 
		
            $headers = null;
            if ($headersAreFirstRow)
                $headers = fgetcsv($fh);
            if (is_array($customHeaders))
                $headers = $customHeaders; // custom header override.
        
            while (($row = fgetcsv($fh)) !== false)
            {
                if (count($row) == 0 or $row[0] === null)
                    continue; // ignore blank lines.
            
    			if ($headers)
    			{
    	            $out = [];
    	            foreach (range(0, count($row)-1) as $i) {
    	                $h = ($i < count($headers)) ? $headers[$i] : $i;
    	                $out[$h] = $row[$i];
    	            }
    				$row = $out;
    				unset($out);
    			}
            
                yield $row;
            }
        }
        finally {
            if (isset($fh) && is_resource($fh)) {
    			@flock($fh, LOCK_UN);
    			@fclose($fh);
            }
        }
    }
    
    /**
     * Import a CSV directly into a DataFrame object in the most memory efficient way.
     * 
     * In the cases were the CSV has no column headers then the supplied array will be in simple
     * sequential order.
     * 
     * -- parameters:
     * @param $filePath Path or URL to the CSV file.
     * @param $columns When TRUE, will take the first row as the headers. When an array is supplied then the array will be used as the column. Passing FALSE or any other value will result in sequential column headers.
     * @param $skipRows	Skip over a specified number of rows at the start. Defaults to 0.
     * 
     * @see Importer::yield_csv() for possible errors or exceptions that may be raised.
     * 
     * @return A DataFrame object containing the rows from the CSV, or NULL if no rows were retrieved.
     */
    static public function csv_dataframe(string $filePath, $columns = false, int $skipRows = 0)
    {
        if (is_array($columns)) {
            $customHeaders = $columns;
            $headersAreFirstRow = false;
        }
        
        else {
            $customHeaders = null;
            $headersAreFirstRow = (bool)$columns;
        }
        
        $df = null;
        foreach (self::yield_csv($filePath, $headersAreFirstRow, $customHeaders, $skipRows) as $row)
        {
            if ($df)
                $df->add_row($row);
            else
                $df = new DataFrame([$row]);
        }
        
        return $df;
    }
    
    /**
     * Split a string of raw data down into rows and columns.
     * 
     * Each row is returned to your callback method as an array of values, where you may do
     * as you desire with it.
     * 
     * Your callback method should be in the format of:
     * 
     * `function myCallback($row)`
     * 
     * where $row is an array of the values retrieved from the current row or line in the data. The supplied
     * array will be in simple sequential order.
     * 
     * -- parameters:
     * @param $callback A callback method to process each row.
     * @param $data The data to be processed.
     * @param $itemDelimiter The token used to split each row into individual items.
     * @param $lineDelimiter The line ending used to split the data into seperate rows or lines.
     * @param $headersAreFirstRow TRUE or FALSE, where are not the first row contains headers.
     * @param $customHeaders A custom set of column headers to override any existing or absent headers.
     * 
     * @return TRUE upon successful completion or the compiled data array when not using a callback. FALSE on failure to process the data source.
     * 
     * This method will generate a user level warning if data is empty or can not otherwise be derived into
     * at least 1 line of applicable data.
     */
    static public function delimitered_data(callable $callback, string $data, string $itemDelimiter, string $lineDelimiter = "\n", bool $headersAreFirstRow = false, $customHeaders = null)
    {
        $lines = explode($lineDelimiter, trim($data));
        $count = count($lines);
        if ($count == 0 or ($count == 1 && $lines[0] === ''))  {
            trigger_error('Provided data can not be broken apart using the provided line delimiter, or the data is empty.', E_USER_WARNING);
            return $callback ? false : [];
            
        }
        
        if ($headersAreFirstRow || is_array($customHeaders))
            $headers = $headersAreFirstRow ? explode($itemDelimiter, array_shift($lines)) : $customHeaders;
		else
			$headers = null;
		
		$data = ($callback) ? null : [];
		
        foreach ($lines as $line) 
		{ 
			$values = explode($itemDelimiter, $line);
			$row = $headers ? array_combine($headers, $values) : $values;
			
            if ($callback)
                $callback($row);
            else
                $data[] = $row;
		}
        
        return is_array($data) ? $data : true;
    }
}