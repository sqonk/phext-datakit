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
    static public function csv_dataframe(string $filePath, $columns = false, int $skipRows = 0): ?DataFrame
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
    
    /**
     * Loads data out of a MySQL database into a DataFrame. $source can either be a table name or 
     * a fully qualified SELECT statement. It is primarily designed as a convienience for quickly getting data 
     * into your script for research or general utility purposes using simplistic queries.
     * 
     * NOTE: Requires the MySQLi extension to be installed and active.
     * 
     * CAUTION: This method is designed for CLI usage only and will trigger a warning if called from
     * any other SAPI. Additionally it performs <u>no</u> escaping or other security checks and so
     * should <u>not</u> be used in any situation where common sense security would be expected or the input 
     * can not be trusted.
     * 
     * -- parameters:
     * @param $database Name of the MySQL database to query.
     * @param $source Either the name of table within the database or a full SELECT statement. 
     * @param $server Server address where the database is hosted. Defaults to 'localhost'.
     * @param $username Username used to log into the database. Defaults to 'root'.
     * @param $password Matching password for the username. Defaults to ''.
     * 
     * @throws InvalidArgumentException If any other kind of SQL query is attempted outside of a SELECT.
     * @throws RuntimeException If the MySQL library generates an error from executing the query.
     * 
     * @return A DataFrame containing the resulting rows. Returns NULL if the specified table or query returns no rows.
     */
    static public function mysql_dataframe(string $database, string $source, string $server = 'localhost', string $username = 'root', string $password = ''): ?DataFrame
    {
        if (php_sapi_name() != 'cli')
            trigger_error("### WARNING: Importer::mysql_dataframe() is designed for CLI usage only. It should **not** be exposed for web usage.", E_USER_WARNING);
        
        $source = trim($source);    
        $lower = strtolower($source);
        
        $restricted = ['update', 'delete', 'insert', 'create', 'alter', 
            'drop', 'event', 'execute', 'grant', 'lock', 'trigger'];
        foreach ($restricted as $action) {
            if (starts_with($lower, "$action "))
                throw new \InvalidArgumentException("This method is designed for data retrieval only. Queries that modify the database are not permitted.");
        }            
        
        if (! starts_with($lower, 'select '))
            $source = sprintf("SELECT * FROM `%s`", $source);
        
        $db = new \mysqli($server, $username, $password, $database);
        $r = $db->query($source);
        
        if (is_object($r)) 
            return $r->num_rows == 0 ? null : dataframe($r->fetch_all(MYSQLI_ASSOC));
        
        else if ($db->errno != 0) 
            throw new \RuntimeException("{$db->errno} {$db->error}\n$source");
    }
    
    /**
     * Loads data out of a SQLite database into a DataFrame. $source can either be a table name or 
     * a fully qualified SELECT statement. It is primarily designed as a convienience for quickly getting data 
     * into your script for research or general utility purposes using simplistic queries.
     * 
     * NOTE: Requires the SQLite3 extension to be installed and active.
     * 
     * -- parameters:
     * @param $database Name of the MySQL database to query.
     * @param $source Either the name of table within the database or a full SELECT statement. 
     * 
     * @throws InvalidArgumentException If any other kind of SQL query is attempted outside of a SELECT.
     * @throws RuntimeException If the SQLite library generates an error from executing the query.
     * 
     * @return A DataFrame containing the resulting rows. Returns NULL if the specified table or query returns no rows.
     */
    static public function sqlite_dataframe(string $filepath, string $source): ?DataFrame
    {
        $source = trim($source); 
        $lower = strtolower($source);
        
        $restricted = ['update', 'delete', 'insert', 'create', 'alter', 
            'drop', 'event', 'execute', 'grant', 'lock', 'trigger'];
        foreach ($restricted as $action) {
            if (starts_with($lower, "$action "))
                throw new \InvalidArgumentException("This method is designed for data retrieval only. Queries that modify the database are not permitted.");
        }            
        
        if (! starts_with($lower, 'select '))
            $source = "SELECT * FROM $source";
        
        $db = new \SQLite3($filepath, SQLITE3_OPEN_READONLY);
        if ($r = $db->query($source)) {
            $rows = [];
            while ($row = $r->fetchArray(SQLITE3_ASSOC))
                $rows[] = $row;
            
            return count($rows) ? dataframe($rows) : null;
        }
        [$error, $msg] = [$db->lastErrorCode(), $db->lastErrorMsg()];
        
        if ($error)
            throw new \RuntimeException("$error {$msg}\n$source");
    }
}