<?php
declare(strict_types = 1);

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

use sqonk\phext\plotlib\BulkPlot;
use sqonk\phext\core\{arrays,strings};

    
/*
	A class for managing and manipulating a series of rows and columns. Arithmetic 
	operations align on both row and column labels where applicable.	
*/
class DataFrame implements \ArrayAccess, \Countable, \IteratorAggregate
{
    protected $data;
    protected $headers = [];
    protected $transformers = [];
    protected $indexHeader = '';
    protected $showHeaders = true;
    protected $showGenericIndexes = true;
	
	// The same as "new DataFrame()".
	static public function make(array $data, array $headers = null)
	{
		return new DataFrame($data, $headers);
	}
	
	// -------- Class Interfaces
	
	public function getIterator()
	{
		return new \ArrayIterator($this->data());
	}
	
	public function offsetSet($index, $row)
	{
		if ($index === null)
			$this->data[] = $row;
		else
			$this->data[$index] = $row;
	}
	
	public function offsetExists($index)
	{
		return $this->row($index) !== null;
	}
	
	public function offsetUnset($index)
	{
		$keys = array_keys($this->data());
		if ($index == LAST_ROW) 
			$index = arrays::last($keys);
		
		else if ($index == FIRST_ROW)
			$index = $keys[0];
		
		$this->drop_rows($index, null, true);
	}
	
	public function offsetGet($index)
	{
		return $this->row($index);
	}
	
	/* 
		Converting the DataFrame to a string produces the report.
	
		See: report()
	*/
    public function __toString()
    {
        return $this->report();
    }
	
	// ------- Main class methods
    
    /*
        Construct a new dataframe with the provided data. You may optionally
        provided the set of column headers in the second parameter. If you 
        choose to do this then they should match the keys in the array.

        NOTE: The provided array must have at least one element/row and
        must also be 2-dimensional in structure.
    */
    public function __construct(array $data, array $headers = null)
    {
        if ($data === null)
            throw new \InvalidArgumentException("Data array can not be null. A valid array must be given.");
        else if (count($data) == 0)
            throw new \LengthException("A DataFrame needs at least one row of data.");
        $this->data = $data;
        $this->validate();  
        $this->headers = $headers;
        if (! $this->headers) {
            $indexes = array_keys($this->data);
            $this->headers = array_keys($this->data[$indexes[0]]);
        }
    }
	
	// Produce an exact replica of the dataframe.
	public function copy()
	{
		return $this->clone($this->data());
	}
    
	/*
		Produce a copy of the dataframe consisting of only the supplied data. All other
		information such as transfomers and header settings remain the same.
	*/
    public function clone($data)
    {
        $copy = new DataFrame($data);
        $copy->transformers = $this->transformers;
        $copy->indexHeader = $this->indexHeader;
        $copy->showGenericIndexes = $this->showGenericIndexes;
        $copy->showHeaders = $this->showHeaders;
        return $copy;
    }
    
	// Internal method.
    protected function validate()
    {
        foreach ($this->data as $item)
        {
            if (! is_array($item))
                throw new \UnexpectedValueException("Invalid array format. Each item of the provided data array must be a sub-array.");
        }
    }
    
	/* 
		Whether or not the DataFrame should display the column
		headers when it is printed. The default is TRUE.
	*/
    public function display_headers($display = null)
    {   
        if ($display === null)
            return $this->showHeaders;
        $this->showHeaders = $display;
        return $this;
    }
    
	/* 
		Whether or not the DataFrame should display the row
		indexes that sequentially numerical when it is printed. 
	
		The default is TRUE.
	
		This is automatically disabled for pivoted DataFrames.
	*/
    public function display_generic_indexes($display = null)
    {
        if ($display === null)
            return $this->showGenericIndexes;
        $this->showGenericIndexes = $display;
        return $this;
    }
    
	/* 
		Set or get the column header currently or to be used
		as the row indexes.
	
		** You should not need to set this. 
	
		See reindex_rows_with_column() instead.
	*/
    public function index($indexHeader = null)
    {
        if ($indexHeader === null)
            return $this->indexHeader;
        $this->indexHeader = $indexHeader;
        return $this;
    }
    
	/*
		Used to set or get the full list of display transformers.
	
		** Used internally. You should not need to call this
		function under normal circumstances. 
	
		See apply_display_transformer() instead.
	*/
    public function transformers($transformers = null)
    {
        if ($transformers === null)
            return $this->transformers;
        $this->transformers = $transformers;
        return $this;
    }
    
	/* 
		Returns TRUE if and only if all values within the given column
		contain a valid number.
	*/
    public function column_is_numeric(string $column)
    {
        $count = 0;
        foreach ($this->data as &$row) {
            if (isset($row[$column]) && is_numeric($row[$column]))
                $count++;
            if ($count > 1)
                return true;
        }
        return false;
    }
    
	/* 
		Return the associative array containing all the data within
		the DataFrame.
	*/
    public function data()
    {
        return $this->data;
    }
    
	/*
		Flatten the DataFrame into a native array.
	
		$includeIndex: 	If TRUE then use the DataFrame indexes as the 
						keys in the array.
		$columns		One or more columns that should be used in the
						resulting array, all columns if null is supplied.
        
        The columns can be supplied as a set of variable arguments or an
        array as the second argument.
	*/
    public function flattened(bool $includeIndex = true, ...$columns)
    {
        if (count($columns) == 1 and is_array($columns[0]))
            $columns = $columns[0];
        $columns = $this->determineColumns($columns);
        $out = [];
        foreach ($this->data as $index => $row) 
        {
            $r = [];
            if ($includeIndex)
                $r[] = $index;
            foreach ($columns as $h)
                $r[] = $row[$h] ?? null;
            $out[] = $r;
        }
            
        return $out;
    }
    
	// Return the row at $index.
    public function row($index)
    {
        if ($index == LAST_ROW) 
			$row = arrays::last($this->data);
		
		else if ($index == FIRST_ROW)
			$row = arrays::first($this->data);
		
		else
			$row = $this->data[$index] ?? null;
		
		return $row;
    }
    
	// Return an array of all the current row indexes.
    public function indexes()
    {
        return array_keys($this->data);
    }
    
	// All column headers currently in the DataFrame.
    public function headers()
    {
        return $this->headers;
    }
    
	/* 
		Return a copy of the DataFrame only containing the number
		of rows from the start as specified by $count.
	*/
    public function head(int $count)
    {
        if ($count >= count($this->data))
            return $this->clone($this->data);
        
        $slice = array_slice($this->data, 0, $count, true);
        return $this->clone($slice);
    }
    
	/* 
		Return a copy of the DataFrame only containing the number
		of rows from the end as specified by $count.
	*/
    public function tail(int $count)
    {
        $total = count($this->data);
        if ($count >= $total)
            return $this->clone($this->data);
        
        $slice = array_slice($this->data, $total-$count, $count, true);
        return $this->clone($slice);
    }
    
	/* 
		Return a copy of the DataFrame only containing the the rows
		starting from $start through to the given length.
	*/
    public function slice(int $start, ?int $length = null)
    {
        $total = count($this->data);
        if ($start >= $total)
            throw new \InvalidArgumentException("Start of slice is greater than the length of the array.");
        if ($length and $start + $length >= $total-1) 
            $length = null;
		
        $slice = array_slice($this->data, $start, $length, true);
        return $this->clone($slice);
    }
    
	/*
		Return a copy of the DataFrame containing a random
		subset of the rows. The minimum and maximum values
		can be supplied to focus the random sample to a 
		more constrained subset.
	*/
    public function sample(int $minimum, ?int $maximum = null)
    {
        $count = count($this->data);
        if ($maximum != null && $maximum < $count)
            $count = $maximum;
        
        $start = $count+1;
        while ($count-$start < $minimum)
            $start = rand(0, $count);
        
        $length = rand($minimum, $count-$start);
        return $this->slice($start, $length);
    }
    
	/*
		Change the name of a column within the DataFrame. If $inPlace
		is TRUE then this operation modifies the receiver otherwise
		a copy is returned.
	*/
    public function change_header(string $column, string $newName, bool $inPlace = false)
    {
        if ($inPlace)
        {
            foreach ($this->data as &$row)
            {
                if (isset($row[$column])) {
                    $row[$newName] = $row[$column];
                    unset($row[$column]);
                }
            }
            return $this;
        }
        else
        {
            $data = $this->data;
            foreach ($data as &$row)
            {
                if (isset($row[$column])) {
                    $row[$newName] = $row[$column];
                    unset($row[$column]);
                }
            }
            return $this->clone($data);
        }
    }
    
	/*
		Reindex the DataFrame using the provided labels. If $inPlace
		is TRUE then this operation modifies the receiver otherwise
		a copy is returned.
	*/
    public function reindex_rows(array $labels, bool $inPlace = false)
    { 
		$values = array_values($this->data);
		if (count($labels) > count($values))
			$labels = array_slice($labels, 0, count($values));
		
		$data = array_combine($labels, $values);
		if ($inPlace) {
			$this->data = $data;
			return $this;
		}
			
		return $this->clone($data);
    }
    
	/*
		Push one of the columns out to become the row index. If $inPlace
		is TRUE then this operation modifies the receiver otherwise
		a copy is returned.
	*/
    public function reindex_rows_with_column(string $column, bool $inPlace = false)
    {
        $df = $this->reindex_rows($this->values($column), $inPlace);
        $df->indexHeader = $column;
        return $df->drop_columns($column, true);
    }
    
	/*
		Filter the DataFrame using the provided callback and one or 
		more columns. If no columns are specified then the operation
		applies to all.
	
		Callback format: myFunc($value, $column, $rowIndex) -> bool
	
		For a row to make it into the filtered set then only ONE
		of the columns need to equate to true from the callback.
	*/
    public function filter(callable $callback, ...$columns)
    {
        $columns = $this->determineColumns($columns);
        if (count($columns) == 0)
            throw new \LengthException("filtering requires at least one column");
        $filtered = [];
        foreach ($this->data as $index => $row) {
            $pass = false;
            foreach ($columns as $column) {
                if (isset($row[$column]) and $callback($row[$column], $column, $index)) {
                    $pass = true;
                    break;
                }
            }
            if ($pass)
                $filtered[$index] = $row;
            
        }
        return (count($filtered) > 0) ? $this->clone($filtered) : null;
    }
    
	/*
		Filter the DataFrame using the provided callback and one or 
		more columns. If no columns are specified then the operation
		applies to all.
	
		Callback format: myFunc($value, $column, $rowIndex) -> bool
	
		For a row to make it into the filtered set then ALL
		of the columns need to equate to true from the callback.
	*/
    public function unanfilter(callable $callback, ...$columns)
    {
        $columns = $this->determineColumns($columns);
        if (count($columns) == 0)
            throw new \LengthException("filtering requires at least one column");
        $filtered = [];
        foreach ($this->data as $index => $row) {
            $pass = true;
            foreach ($columns as $column) {
				if (! isset($row[$column]) or ! $callback($row[$column], $column, $index)) {
                    $pass = false;
                    break;
                }
            }
            if ($pass)
                $filtered[$index] = $row;
        }
        return (count($filtered) > 0) ? $this->clone($filtered) : null;
    }
    
	/*
		Filter the DataFrame using the provided callback and one or 
		more columns. If no columns are specified then the operation
		applies to all.
	
		Callback format: myFunc($row, $rowIndex) -> bool
	
		This function differs from filter() and unanfilter() in that
		it passes the whole row to the callback. This is useful
		if your condition of inclusion requires cross comparing
		data across columns within the row.
	*/
    public function ufilter(callable $callback)
    {
        $filtered = [];
        foreach ($this->data as $index => $row) {
            if ($callback($row, $index)) 
                $filtered[$index] = $row;
        }
        return (count($filtered) > 0) ? $this->clone($filtered) : null;
    }
    
	/*
		Sort the DataFrame via one or more columns.
		
		If the last parameter passed in is either TRUE or FALSE
		then it will determine the direction in which the dataframe
		is ordered. The default is ascending (TRUE).
	*/
    public function sort(...$columns)
    {
		$asc = true;
		if (count($columns) > 0 && is_bool(arrays::last($columns))) 
			$asc = array_pop($columns);
		
        $columns = $this->determineColumns($columns);
        if (count($columns) == 0)
            throw new \LengthException("sorting requires at least one column");
		
        uasort($this->data, function($a, $b) use ($columns, $asc) {
            $cmp = 0;
			if (! $asc)
				[$a, $b] = [$b, $a];
            foreach ($columns as $column)
            {
				$cmp = arrays::get($a, $column) <=> arrays::get($b, $column);
                if ($cmp !== 0)
                    break; // non-0 result will break out.
            }
            return $cmp;
        });
        return $this;
    }
    
	/*
		Sort the DataFrame using a callback and one or more columns.
	
		Callback format: myFunc($value1, $value2, $column) -> bool
	*/
    public function usort(callable $callback, ...$columns)
    {
        $columns = $this->determineColumns($columns);
        if (count($columns) == 0)
            throw new \LengthException("sorting requires at least one column");
		
        uasort($this->data, function($a, $b) use ($columns, $callback) {
            $cmp = 0;
            foreach ($columns as $column)
            {
                $cmp = $callback(arrays::get($a, $column), arrays::get($b, $column), $column);
                if ($cmp !== 0)
                    break;
            }
            return $cmp;
        });
        return $this;
    }
    
	// Return an array containing both the number of rows and columns.
    public function shape()
    {
        $cols = 0;
        foreach ($this->data as $row) {
            $cnt = count($row);
            if ($cnt > $cols)
                $cols = $cnt;
        }
        return [count($this->data), $cols];
    }

    
	/* 
		If a column is specified then return the number of rows
		containing a value for it.
	
		If no column is given then return a new DataFrame containing
		the counts for all columns.
	*/
    public function size($column = null)
    {
        if ($column)
        {
            return count($this->values($column, false));
        }
        else
        {
            $r = [];
            foreach ($this->headers as $h) {
                $values = $this->values($h, false);
                $r[$h] = count($values);
            }
            return $this->clone([$r]);
        }
    }
    
	// Return the number of rows.
    public function count()
    {
        return count($this->data);
    }
    
	// Internal functon.
    protected function determineColumns($columns)
    {
        if (! $columns || (is_array($columns) && count($columns) == 0))
            $columns = $this->headers;
        else if (! is_array($columns))
            $columns = [$columns];
        return $columns;
    }
	
	/*
		Return a two-dimensional native array containing the
		values for the given columns of all rows in the dataframe.	
	
		If no columns are specified then all are used.
	*/
	public function matrix(...$columns)
	{
		$columns = $this->determineColumns($columns);   
		$matrix = [];
		
		foreach ($this->data() as $row) 
			foreach ($columns as $col)
				$matrix[] = $row[$col];
		
		return $matrix;
	}
    
	/* 
		Return all values for the given column. If $filterNAN is
		TRUE then omit values that are NULL.
	*/
    public function values($columns = null, bool $filterNAN = true)
    {
        $columns = $this->determineColumns($columns);        
        
        $r = [];
        foreach ($columns as $h) 
        {
            $values = [];
            foreach ($this->data as $row) {
                $v = array_key_exists($h, $row) ? $row[$h] : null;
                if ($v !== null || ! $filterNAN)
                    $values[] = $v;
            }
            $r[] = $values;
        }
        return (count($r) == 1) ? $r[0] : $r;
    }

	/*
		Produce a formatted string, suitable for outputing to
		the commandline or browser, detailing all rows and
		the desired columns. If no columns are specified then 
		all columns are used.
	*/
    public function report(...$columns)
    {
        $columns = $this->determineColumns($columns); 
        $data = [];
        
        // filters the transformers to the set applicable for this report.
        $trs = [];
        foreach ($this->transformers as $h => $tf) { 
            if ((arrays::contains($columns, $h) || $this->indexHeader == $h) && $tf) {
                $trs[$h] = $tf;
            }
        } 
        
        foreach ($this->data as $index => $row) {
            foreach ($trs as $h => $tf) { 
                if (isset($row[$h])) 
                    $row[$h] = $tf($row[$h]);
                else if ($this->indexHeader == $h) {
                    $index = $tf($index); 
                } 
            }
            
            $data[$index] = $row;
        }
        
        return strings::columnize($data, $columns, $this->showHeaders, $this->showGenericIndexes);
    }
    
	/* 
		Print to stdout the report for this DataFrame.
	
		See: report()
	*/
    public function print(...$columns)
    {
        println($this->report(...$columns));
    }
    
	/*
		Provide a maximum or minimum (or both) constraint for the values on column.
	
		If a row's value for the column exceeds that constraint then it will be set
		to the constraint.
	
		If either the lower or upper constraint is not needed then passing in
		null will ignore it.
	
		If no column is specified then the constraints apply to all columns.
	
		If $inPlace is TRUE then this operation modifies the receiver otherwise
		a copy is returned.
	*/
    public function clip($lower, $upper, string $column = null, bool $inplace = false)
    {
        if ($inplace)
        {
            foreach ($this->data as &$row)
            {
                if ($column !== null) 
                {
                    if (isset($row[$column]))
                    {
                        $value = $row[$column]; 
                        if ($lower !== null && is_numeric($value) && $value < $lower) 
                            $row[$column] = $lower;
                        else if ($upper !== null && is_numeric($value) && $value > $upper)
                            $row[$column] = $upper;
                    }
                }
                else {
                    foreach ($row as $key => $value) {
                        if ($lower !== null && is_numeric($value) && $value < $lower) 
                            $row[$key] = $lower;
                        else if ($upper !== null && is_numeric($value) && $value > $upper) 
                            $row[$key] = $upper;
                    }
                }
            }
            return $this;
        }
        else
        {
            $modified = [];
            foreach ($this->data as $row)
            {
                if ($column !== null) 
                {
                    if (isset($row[$column]))
                    {
                        $value = $row[$column];
                        if ($lower !== null && is_numeric($value) && $value < $lower) 
                            $row[$column] = $lower;
                        else if ($upper !== null && is_numeric($value) && $value > $upper) 
                            $row[$column] = $upper;
                    }
                }
                else {
                    foreach ($row as $key => $value) {
                        if ($lower !== null && is_numeric($value) && $value < $lower) 
                            $row[$key] = $lower;
                        else if ($upper !== null && is_numeric($value) && $value > $upper) 
                            $row[$key] = $upper;
                    }
                }
                $modified[] = $row;
            }
            return $this->clone($modified);
        }
    }
    
	/*
		Remove any rows where the value of the provided column exeeds the provided
		lower or upper boundary, for a given column.
	
		If either the lower or upper constraint is not needed then passing in
		null will ignore it.
	
		If no column is specified then the filter applies to all columns.
	
		If $inPlace is TRUE then this operation modifies the receiver otherwise
		a copy is returned.
	*/
    public function prune($lower, $upper, $column = null, $inplace = false)
    {
        if ($inplace)
        {
            foreach ($this->data as &$row)
            {
                if ($column !== null) {
                    if (isset($row[$column]))
                    {
                        $value = $row[$column];
                        if ($lower !== null && is_numeric($value) && $value < $lower)
                            unset($row[$column]);
                        if ($upper !== null && is_numeric($value) && $value > $upper)
                            unset($row[$column]);
                    }
                }
                else {
                    foreach ($row as $key => $value) {
                        if ($lower !== null && is_numeric($value) && $value < $lower) 
                            unset($row[$key]);
                        else if ($upper !== null && is_numeric($value) && $value > $upper) 
                            unset($row[$key]);
                    }
                }
            }
            return $this;
        }
        else
        {
            $modified = [];
            foreach ($this->data as $row)
            {
                if ($column !== null) 
                {
                    if (isset($row[$column]))
                    {
                        $value = $row[$column];
                        if ($lower !== null && is_numeric($value) && $value < $lower)
                            unset($row[$column]);
                        else if ($upper !== null && is_numeric($value) && $value > $upper) 
                            unset($row[$column]);
                    }
                }
                else {
                    foreach ($row as $key => $value) {
                        if ($lower !== null && is_numeric($value) && $value < $lower) 
                            unset($row[$key]);
                        else if ($upper !== null && is_numeric($value) && $value > $upper) 
                            unset($row[$key]);
                    }
                }
                $modified[] = $row;
            }
            return $this->clone($modified);
        }
    }
    
	/*
		Return a new DataFrame containing the rows where the values of the
		given column exceed a lower and/or upper boundary.
	
		If either the lower or upper constraint is not needed then passing in
		null will ignore it.
	
		If no column is specified then the filter applies to all columns.
	*/
    public function oob($lower, $upper, $column = null)
    {
        $data = [];
        $count = count($this->data);
        $keys = array_keys($this->data);
        for ($i = 0; $i < $count; $i++)
        {
            $index = $keys[$i];
            $row = $this->data[$index];
            
            if ($column !== null) 
            {
                if (isset($row[$column]))
                {
                    $value = $row[$column];
                    $r = ['column' => $column, 'lower' => null, 'upper' => null];
                
                    if ($lower !== null && is_numeric($value) && $value < $lower) 
                        $r['lower'] = $value;
                    else if ($upper !== null && is_numeric($value) && $value > $upper) 
                        $r['upper'] = $value;
                    
                    $data[$index] = $r;
                }
            }
            else 
            {
                $r = [];
                foreach ($row as $key => $value) 
                {
                    if ($lower !== null && is_numeric($value) && $value < $lower) {
                        $r = [
                            'index' => $index,
                            'column' => $key,
                            'lower' => $value,
                            'upper' => null
                        ];
                    }
                    else if ($upper !== null && is_numeric($value) && $value > $upper) {
                        $r = [
                            'index' => $index,
                            'column' => $key,
                            'upper' => $value,
                            'lower' => null,
                        ];
                    }
                    $data[] = $r;
                }
            }
        }
        return new DataFrame($data);
    }
    
	/*
		Return a new 2-column DataFrame containing both the start and endpoints
		where valus for a specific column exceed a given threshold.
	
		$direction can be OOB_LOWER, OOB_UPPER or OOB_ALL to dertermining if
		the threshhold is calculated as a minimum boundary, maximum boundary or
		either.
	
		Where oob() simply returns all the rows that exceed the threshold, this
		method will return a DataFrame of regions, where the start and end
		values refer to the row indexes of the current DataFrame.
	*/
    public function oob_region($theshhold, $direction, string $column)
    {
        $data = [];
        $current = null;
        $last = null;
        foreach ($this->data as $index => $row)
        {
            if (! isset($row[$column]))
                continue;
            
            $value = $row[$column];
            if (is_numeric($value) and 
                (($direction == OOB_LOWER || $direction == OOB_ALL) && $value < $theshhold) or
                    (($direction == OOB_UPPER || $direction == OOB_ALL) && $value > $theshhold)
            )
            {
                if (! $current) {
                    $current = ['start' => $index];
                    $last = null;
                }
                else
                    $last = $index;
            }
            else if ($current)
            {
                $current['end'] = $last ?? $current['start'];
                $data[] = $current;
                $current = null;
            }
        }
        
        if ($current)
        {
            // terminate final region.
            $current['end'] = $last ?? $current['start'];
            $data[] = $current;
        }
        
        return count($data) > 0 ? new DataFrame($data) : null;
    }
    
	/*
		Return a new 3-column DataFrame containing areas in the current where
		running values in a column exceed the given amount.
	
		For example, if you have a column of timestamps and those timestamps
		typically increase by N minutes per row, then this method can be used to 
		find possible missing rows where the jump time is greater than the expected
		amount.
	
		For every row where the given amount is exceeded, a row in the resulting
		DataFrame will exist where 'start' and 'end' list the values where the
		gap was found. A third column 'segments' details how many multiples of
		the amount exist between the values.
	
		Providing a column name to $resultColumn allows you to perform the
		comparison in one column while filling the resulting DataFrame with
		referenced values from another column.
	*/
    public function gaps($amount, string $usingColumn, string $resultColumn = '')
    {
        $result = [];
        $last = null;
        $lastRow = null;
        foreach ($this->data as $index => $row)
        {
            $current = arrays::safe_value($row, $usingColumn, null);
            if ($current !== null)
            {
                if ($last !== null && $current - $last > $amount) {
                    $count = floor(($current - $last) / $amount)-1;
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
        return count($result) > 0 ? new DataFrame($result) : null;
    }
    
	/*
		Returns TRUE if ANY of the rows for a given column match
		the given value.
	
		If no column is specified then the the check runs over
		all columns.
	*/
    public function any($value, string $column = null)
    {
        foreach ($this->data as $row)
        {
            if ($column !== null) 
            {
                if (isset($row[$column]))
                {
                    if ($row[$column] == $value)
                        return true;
                }
            }
            else
            {
                foreach ($row as $key => $v) {
                    if ($v == $value)
                        return true;
                }
            }
        }
        return false;
    }
    
	/*
		Returns TRUE if ALL of the rows for a given column match
		the given value.
	
		If no column is specified then the the check runs over
		all columns.
	*/
    public function all($value, $column = null)
    {
        foreach ($this->data as $row)
        {
            if ($column !== null) 
            {
                if (isset($row[$column]))
                {
                    if ($row[$column] != $value)
                        return false;
                }
            }
            else
            {
                foreach ($row as $key => $v) {
                    if ($v != $value)
                        return false;
                }
            }
        }
        return true;
    }
    
	/*
		Convert all values in a given column to their absolute
		value.
	
		If no column is specified then the the operation runs over
		all columns.
	
		If $inPlace is TRUE then this operation modifies the current
		DataFrame, otherwise a copy is returned.
	*/
    public function abs($column = null, $inplace = false)
    {
        if ($inplace)
        {
            foreach ($this->data as &$row)
            {
                if ($column !== null) 
                {
                    if (isset($row[$column]))
                    {
                        $value = $row[$column];
                        if (is_numeric($value) && $value < 0) 
                            $row[$column] = abs($value);
                    }
                }
                else 
                {
                    foreach ($row as $key => $value) {
                        if (is_numeric($value) && $value < 0) 
                            $row[$key] = abs($value);
                    }
                }
            }
            return $this;
        }
        else
        {
            $modified = [];
            foreach ($this->data as $row)
            {
                if ($column !== null) 
                {
                    if (isset($row[$column]))
                    {
                        $value = $row[$column];
                        if (is_numeric($value) && $value < 0)
                            $row[$column] = abs($value);
                    }
                }
                else 
                {
                    foreach ($row as $key => $value) {
                        if (is_numeric($value) && $value < 0)
                            $row[$key] = abs($value);
                    }
                }
                $modified[] = $row;
            }
            return $this->clone($modified);
        }
    }
    
	/*
		Compute a standard deviation of one or more columns.
	
		If no column is specified then the the operation runs over
		all columns.
	
		If exactly one column is supplied then a single value is 
		returned, otherwise a DataFrame of 1 value per column is
		produced.
	
		$sample is passed through to the standard deviation calculation
		to determine how the result is producted.
	*/
    public function std(bool $sample = false, ...$columns)
    {
        $columns = $this->determineColumns($columns);
        if (count($columns) == 1)
        {
            $values = $this->values($columns[0]);
            return math::standard_deviation($values, $sample);
        }
        else
        {
            $r = [];
            foreach ($columns as $h) {
                $values = $this->values($h);
                $std = math::standard_deviation($values, $sample);
                $r[$h] = $std;
            }
            return $this->clone([$r]);
        }
    }
    
	/*
		Compute a sum of one or more columns.
	
		If no column is specified then the the operation runs over
		all columns.
	
		If exactly one column is supplied then a single value is 
		returned, otherwise a DataFrame of 1 value per column is
		produced.
	*/
    public function sum(...$columns)
    {
        $columns = $this->determineColumns($columns);
        if (count($columns) == 1)
        {
            return array_sum($this->values($columns[0]));
        }
        else
        {
            $r = [];
            foreach ($columns as $h) {
                $values = $this->values($h);
                if ($this->column_is_numeric($h)) {
                    $r[$h] = array_sum($values);
                }
                else
                    $r[$h] = (count($values)) ? $values[0] : '';
            }
            return $this->clone([$r]);
        }
    }
    
	/*
		Compute a cumulative sum of one or more columns.
	
		If no column is specified then the the operation runs over
		all columns.
	
		If exactly one column is supplied then a single value is 
		returned, otherwise a DataFrame of 1 value per column is
		produced.
	*/
    public function cumsum(...$columns)
    {
        $columns = $this->determineColumns($columns);
        if (count($columns) == 1)
        {
            return math::cumulative_sum($this->values($columns[0]));
        }
        else
        {
            $r = $this->data();
            foreach ($columns as $h) 
			{
                if ($this->column_is_numeric($h)) {
                    $sum_values = math::cumulative_sum($this->values($h));
					foreach ($sum_values as $i => $v)
						$r[$i][$h] = $v;
                }
            }
            return $this->clone($r);
        }
    }
    
	/*
		Compute the average of one or more columns.
	
		If no column is specified then the the operation runs over
		all columns.
	
		If exactly one column is supplied then a single value is 
		returned, otherwise a DataFrame of 1 value per column is
		produced.
	*/
    public function avg(...$columns)
    {
        $columns = $this->determineColumns($columns);
        if (count($columns) == 1)
        {
            return math::avg($this->values($columns[0]));
        }
        else
        {
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
    
	/*
		Return the maximum value present for one or more columns.
	
		If no column is specified then the the operation runs over
		all columns.
	
		If exactly one column is supplied then a single value is 
		returned, otherwise a DataFrame of 1 value per column is
		produced.
	*/
    public function max(...$columns)
    {
        $columns = $this->determineColumns($columns);
        if (count($columns) == 1)
        {
            return max($this->values($columns[0]));
        }
        else
        {
            $r = [];
            foreach ($columns as $h) {
                if ($this->column_is_numeric($h)) {
                    $values = $this->values($h);
                    $std = max($values);
                    $r[$h] = $std;
                }
            }
            return $this->clone([$r]);
        }
    }
    
	/*
		Return the minimum value present for one or more columns.
	
		If no column is specified then the the operation runs over
		all columns.
	
		If exactly one column is supplied then a single value is 
		returned, otherwise a DataFrame of 1 value per column is
		produced.
	*/
    public function min(...$columns)
    {
        $columns = $this->determineColumns($columns);
        if (count($columns) == 1)
        {
            return min($this->values($columns[0]));
        }
        else
        {
            $r = [];
            foreach ($columns as $h) {
                if ($this->column_is_numeric($h)) {
                    $values = $this->values($h);
                    $std = min($values);
                    $r[$h] = $std;
                }
            }
            return $this->clone([$r]);
        }
    }
    
	/*
		Compute the cumulative maximum value for one or more
		columns.
	
		If no column is specified then the the operation runs over
		all columns.
	
		If exactly one column is supplied then a single value is 
		returned, otherwise a DataFrame of 1 value per column is
		produced.
	*/
    public function cummax(...$columns)
    {
        $columns = $this->determineColumns($columns);
        if (count($columns) == 1)
        {
            return math::cumulative_max($this->values($columns[0]));
        }
        else
        {
            $r = $this->data();
            foreach ($columns as $h) {
                if ($this->column_is_numeric($h)) {
                    $max_values = math::cumulative_max($this->values($h));
					foreach ($max_values as $i => $v)
						$r[$i][$h] = $v;
                }
            }
            return $this->clone($r);
        }
    }
    
	/*
		Compute the cumulative minimum value for one or more
		columns.
	
		If no column is specified then the the operation runs over
		all columns.
	
		If exactly one column is supplied then a single value is 
		returned, otherwise a DataFrame of 1 value per column is
		produced.
	*/
    public function cummin(...$columns)
    {
        $columns = $this->determineColumns($columns);
        if (count($columns) == 1)
        {
            return math::cumulative_min($this->values($columns[0]));
        }
        else
        {
            $r = $this->data();
            foreach ($columns as $h) {
                if ($this->column_is_numeric($h)) {
                    $min_values = math::cumulative_min($this->values($h));
					foreach ($min_values as $i => $v)
						$r[$i][$h] = $v;
                }
            }
            return $this->clone($r);
        }
    }
    
	/*
		Find the median value for one or more columns.
	
		If no column is specified then the the operation runs over
		all columns.
	
		If exactly one column is supplied then a single value is 
		returned, otherwise a DataFrame of 1 value per column is
		produced.
	*/
    public function median(...$columns)
    {
        $columns = $this->determineColumns($columns);
        if (count($columns) == 1)
        {
            return math::median($this->values($columns[0]));
        }
        else
        {
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
    
	/*
		Compute the product for one or more columns.
	
		If no column is specified then the the operation runs over
		all columns.
	
		If exactly one column is supplied then a single value is 
		returned, otherwise a DataFrame of 1 value per column is
		produced.
	*/
    public function product(...$columns)
    {
        $columns = $this->determineColumns($columns);
        if (count($columns) == 1)
        {
            return array_product($this->values($columns[0]));
        }
        else
        {
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
    
	/*
		Compute the cumulative product for one or more columns.
	
		If no column is specified then the the operation runs over
		all columns.
	
		If exactly one column is supplied then a single value is 
		returned, otherwise a DataFrame of 1 value per column is
		produced.
	*/
    public function cumproduct(...$columns)
    {
        $columns = $this->determineColumns($columns);
        if (count($columns) == 1)
        {
            return math::cumulative_prod($this->values($columns[0]));
        }
        else
        {
            $r = $this->data();
            foreach ($columns as $h) {
                if ($this->column_is_numeric($h)) {
                    $prod_values = math::cumulative_prod($this->values($h));
					foreach ($prod_values as $i => $v)
						$r[$i][$h] = $v;
                }
            }
            return $this->clone($r);
        }
    }
    
	/*
		Compute the variance for one or more columns.
	
		If no column is specified then the the operation runs over
		all columns.
	
		If exactly one column is supplied then a single value is 
		returned, otherwise a DataFrame of 1 value per column is
		produced.
	*/
    public function variance(...$columns)
    {
        $columns = $this->determineColumns($columns);
        if (count($columns) == 1)
        {
            return math::variance($this->values($columns[0]));
        }
        else
        {
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
    
	/*
		Compute the value for a given quartile for one or more columns.
	
		If no column is specified then the the operation runs over
		all columns.
	
		If exactly one column is supplied then a single value is 
		returned, otherwise a DataFrame of 1 value per column is
		produced.
	*/
    public function quartile($quartile, $column = null)
    {
        if ($column)
        {
            return math::quartile($this->values($column), $quartile);
        }
        else
        {
            $r = [];
            foreach ($this->headers as $h) {
                if ($this->column_is_numeric($h)) {
                    $values = $this->values($h);
                    $std = math::quartile($values, $quartile);
                    $r[$h] = $std;
                }
            }
            return $this->clone([$r]);
        }
    }
    
	/*
		Round all values in the DataFrame up or down to the given
		decimal point precesion.
	*/
    public function round($precision, int $mode = PHP_ROUND_HALF_UP)
    {
        foreach ($this->data as &$row)
        {
            foreach ($row as $key => $value) {
                if (is_numeric($value)) {
                    $row[$key] = round($value, $precision, $mode);
                }
                    
            }
        }
        return $this;
    }
    
	/*
		Run a correlation over one or more columns to find similarities in values.
	
		If $runByColumns is TRUE then the comparison runs horizontally through the
		desired columns, others the comparison runs vertically.
	
		If no column is specified then the the operation runs over
		all columns.
	
		The resulting DataFrame is a matrix of values representing closeness of the
		ajoining values.
	*/
    public function correlation(string $method, array $columns = null, bool $runByColumns = true)
    {
        $columns = $this->determineColumns($columns);
        $cnt = count($columns);
        
        $matrix = [];
        if ($runByColumns)
        {
            if ($cnt < 2) {
                throw new \LengthException("The DataFrame needs to have at least two columns for the requested correlation to work.");
            }
            foreach ($columns as $h)
                $matrix[] = $this->values($h);
        }
        else
        {
            if (count($this->data) < 2) {
                throw new \LengthException("The DataFrame needs to have at least two rows for the requested correlation to work.");
            }
            foreach ($this->data as $row) 
            {
                $mrow = [];
                foreach ($columns as $h)
                    $mrow[] = $row[$h];
                $matrix[] = $mrow;
            }
        } 
        return new DataFrame($this->correlation_matrix($matrix, $method));
    }
    
    // Column and row structure must inversed for this to work.
    protected function correlation_matrix(array $matrix, string $method)
    {
        $accepted_methods = ['pearson', 'spearman'];
        if (! arrays::contains($accepted_methods, $method)) {
            throw new \InvalidArgumentException("$method is not a support correlation method");
        }
        $result = [];
        $columns = count($matrix);
        for ($outer = 0; $outer < $columns; $outer++)
        {
            for ($inner = 0; $inner < $columns; $inner++)
            {
                if (isset($result[$inner][$outer])){
                     $result[$outer][$inner] = $result[$inner][$outer];
                     continue;
                }
                if ($inner == $outer){
                     $result[$outer][$inner] = 1;
                     continue;
                }      
                if ($method == 'pearson')
                    $r = math::correlation_pearson($matrix[$outer], $matrix[$inner]);
                else if ($method == 'spearman')
                     $r = math::correlation_spearman($matrix[$outer], $matrix[$inner]);
                $result[$outer][$inner] = $r;
                
                if ($result[$outer][$inner] == NULL) 
                    return null;
            }
        }
        return $result;
    }
    
	/*
		Produce a formatted string containing a summary of the DataFrame,
		including:
			- row count
			- standard deviation for each column
			- average for each column
			- minimum value for eachc column 
			- quartiles for 25%, 50% and 75%
			- maximum value for eachc column 
	
		If any of the columns have a display transformer attached, then
		they will be formatted accordingly prior to output.
	*/
    public function summary()
    {
        $count = $this->size()->data();
        $std = $this->std()->data();
        $avg = $this->avg()->data();
        $min = $this->min()->data();
        $max = $this->max()->data();
        $q25 = $this->quartile(0.25)->data();
        $q50 = $this->quartile(0.5)->data();
        $q75 = $this->quartile(0.75)->data();
        
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
            if ($tf) {
                foreach ($data as $index => &$row) {
                    if (isset($row[$h])) {
                        $row[$h] = $tf($row[$h]);
                    }
                    else if ($this->indexHeader == $h) {
                        // apply transformer to the index.
                        $data[$tf($index)] = $row;
                        unset($data[$index]);
                    }
                }
            }
        }
        
        $str = strings::columnize($data, $sf->headers);
        
        $shape = $this->shape();
        $str .= "\n".sprintf("[%s rows x %s columns]", $shape[0], $shape[1]);
        
        return $str;
    }
    
	/*
		Produce a set of seperate DataFrames whereby all rows
		of the current DataFrame are split by the given column.
	
		The result is a GroupedDataFrame, containing all resulting
		DataFrames within.
	*/
    public function groupby(string $column)
    {
        $groups = [];
        $na = [];
        $current = null;
        $currentL = '';
        foreach ($this->data as $index => $row)
        {
            $cval = $row[$column] ?? null;
            if ($cval !== null) {
                if (! $currentL || $currentL != $cval) {
                    if ($current)
                        $groups[$currentL] = $current;
                    $current = $groups[$cval] ?? [];
                    $currentL = $cval;
                }
                $current[$index] = $row;
            }
            else {
                $na[$index] = $row;
            }
        }
        if (count($na) > 0)
            $groups['__NA'] = $na;
        if ($current) 
            $groups[$currentL] = $current;
                
        $dfs = []; 
        foreach ($groups as $key => $items) {
            $dfs[$key] = $this->clone($items);
        }
        
        return new GroupedDataFrame($dfs, $column);
    }
    
	/*
		Remove the specified columns from the DataFrame.
	
		If $inPlace is TRUE then this operation modifies the 
		current DataFrame, otherwise a copy is returned.
	*/
    public function drop_columns($columns, $inplace = false)
    {
        $columns = $this->determineColumns($columns);
        
        if ($inplace)
        {
            foreach ($this->data as &$row)
            {
                foreach ($columns as $column) {
                    if (isset($row[$column])) {
                        unset($row[$column]);
                    }
                }
            }
            $this->data = arrays::compact($this->data);
            $headers = [];
            foreach ($this->headers as $h) {
                foreach ($columns as $column) {
                    if ($h != $column)
                        $headers[] = $h;
                }
            }
            $this->headers = $headers;
            foreach ($columns as $column) {
                if (isset($this->transformers[$column]) && $column != $this->indexHeader) 
                    unset($this->transformers[$column]);
            }
            
            return $this;
        }
        else
        {
            $modified = [];
            foreach ($this->data as $index => $row)
            {
                foreach ($columns as $column) {
                    if (isset($row[$column])) {
                        unset($row[$column]);
                    }
                }
                $modified[$index] = $row;
            }
            $copy = $this->clone($modified);
            foreach ($columns as $column) {
                if (isset($copy->transformers[$column]) && $column != $copy->indexHeader)
                    unset($copy->transformers[$column]);
            }
            return $copy;
        }
    }
    
	/*
		Remove the rows starting at $start and ending at $end from
		the DataFrame, where $start and $end represent the relevant
		row indexes.
	
		If $inPlace is TRUE then this operation modifies the 
		current DataFrame, otherwise a copy is returned.
	*/
    public function drop_rows($start, $end = null, $inplace = false)
    {
        if (is_numeric($start) and is_numeric($end)) {
            if ($inplace) {
                for ($i = $start; $i <= $end; $i++)
                    unset($this->data[$i]);
                return $this;
            }
            else {
                $modified = $this->data;
                for ($i = $start; $i <= $end; $i++)
                    unset($modified[$i]);
                return $this->clone($modified);
            }
        }
        else {
            if ($inplace) {
                unset($this->data[$start]);
                return $this;
            }
            else {
                $modified = $this->data;
                unset($modified[$start]);
                return $this->clone($modified);
            }
        }
    }
    
	/* 
		Find all duplicate values for a given set of columns, or
		every column if none are supplied.
	
		This method only compares corresponding values between rows
		of each column. That is, it the comparison is performed
		vertically, not horizontally.
	*/
    public function duplicated(...$columns)
    {
        $columns = $this->determineColumns($columns);
        
        // generate a hashed string of every row for comparison.
        $hashTable = [];
        foreach ($this->data as $key => $row)
        {
            $values = [];
            foreach ($columns as $h)
                $values[] = $row[$h];
            $hashTable[$key] = md5(implode('|', $values));
        }
        
        $matches = [];
        foreach ($hashTable as $currentK => $currentHash) 
        {
            if (arrays::contains($matches, $currentHash))
                continue;
            
            $matched = [];
            foreach ($hashTable as $key => $hash) {
                if ($key != $currentK && $hash == $currentHash)
                    $matched[] = $key;
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
		Drop all duplicates values within the given columns, or
		every column if none are supplied.
	
		If $inplace is TRUE then this operation is performed on 
		receiver, otherwise a modified copy is returned.
	
		See duplicated() for more information.
	*/
    public function drop_duplicates($inplace = false, ...$columns)
    {
        $duplicates = $this->duplicated(...$columns);
        
        if ($inplace)
        {
            foreach ($duplicates as $indexes)
            {
                array_shift($indexes); // ignore the first data set (the original).
                for ($i = count($indexes)-1; $i > -1; $i--) {
                     unset($this->data[$indexes[$i]]); 
                }
            }
        }
        else
        {
            $data = $this->data;
            foreach ($duplicates as $indexes)
            {
                array_shift($indexes); // ignore the first data set (the original).
                for ($i = count($indexes)-1; $i > -1; $i--)
                    unset($data[$indexes[$i]]);
            }
            return $this->clone(arrays::compact($data));
        }
        return $this;
    }
    
	/*
		Generate a copy of the DataFrame with the columns
		and row indexes rotated to become the other.
	
		This has the effect of grouping common values under
		a singular index.
	
		If a set of columns are provided then all other 
		columns are stripped out of the result.
	*/
    public function pivot(...$columns)
    {
        $columns = $this->determineColumns($columns);
        
        $values = [];
        foreach ($columns as $h)
        {
            $ticker = 0;
            foreach ($this->data as $index => $row) {
                $newRow = [
                    '_index' => $index,
                    '_value' => $row[$h] ?? null
                ];
                if (! $ticker) 
                    $values[$h] = $newRow;
                else
                    $values[] = $newRow;
                $ticker++;
            }
        }

        $df = new DataFrame($values);
        $df->display_headers(false);
        $df->display_generic_indexes(false);
        return $df;
    }
	
    /*
		The reverse operation of pivot(). Rotate the row
		indexes and columns back in the other direction.
	
		Note that $columns in this method actually refer
		to the current grouped indexes that you wish to
		revert back into actual columns. 
	
		If no columns are supplied then all indexes are 
		used.
	*/
    public function depivot(...$columns)
    {
        if (! arrays::contains($this->headers, '_index') or ! arrays::contains($this->headers, '_value'))
            throw new Exception('This dataframe is not a pivot frame and there can be de-pivoted');
        
        if (count($columns) == 0)
        {
            $columns = [];
            foreach (array_keys($this->data) as $k) {
                if (! is_int($k))
                    $columns[] = $k;
            }
        }
        $indexes = array_unique($this->values('_index', false));
        
        $rows = [];
        foreach ($indexes as $ind)
        {
            $newRow = [];
            $currentH = '';
            $value = '';
            foreach ($this->data as $h => $row) {
                if (! is_int($h)) 
                    $currentH = $h;
                if ($row['_index'] == $ind && arrays::contains($columns, $currentH)) {
                    $newRow[$currentH] = $row['_value'];
                }
            }
            
            $rows[$ind] = $newRow;
        }
        
        return new DataFrame($rows);
    }
    
	/*
		Perform a complex transformation on the DataFrame where
		by the column specified by $groupColumn becomes the index
		and all other columns are merged via the merge map.
	
		The $mergeMap is an associative array where by each column
		name specified as a key becomes a column in the resulting 
		DataFrame and each column name specified as a value in the 
		array becomes the corresponding value of that column.
	*/
    public function transpose(string $groupColumn, array $mergeMap)
    {
        $this->sort($groupColumn);
        return new DataFrame(arrays::transpose($this->data, $groupColumn, $mergeMap));
    }
    
	/*
		Transform the value of one or more columns using the provided
		callback. If no columns are specified then the operation
		applies to all.
	
		Callback format: myFunc($value, $columnName, $rowIndex)
	*/
    public function transform($callback, ...$columns)
    {
        $columns = $this->determineColumns($columns);
        foreach ($this->data as $index => &$row)
        {
            foreach ($columns as $h) {
                if (isset($row[$h])) {
                    $row[$h] = $callback($row[$h], $h, $index);
                }
            }
        }
        return $this;
    }
    
	/*
		Add a new row to the DataFrame. $row is an associative
		array where the keys should correspond to one or more
		of the column headers in the DataFrame.
	
		** Do not use new or unknown keys not already present
		in the DataFrame.
	*/
    public function add_row(array $row = [], $key = '')
    {
        if ($key !== '')
            $this->data[$key] = $row;
        else
            $this->data[] = $row;
    }
    
	/*
		Add a new column to the DataFrame using the provided
		callback to supply the data. The callback will be called
		for every row currently in the DataFrame.
	
		Callback format: myFunc($row, $rowIndex)
			- $row: associative array containing the value for each column.
	*/
    public function add_column(string $column, callable $callback)
    {
        foreach ($this->data as $index => &$row)
        {
            $row[$column] = $callback($row, $index);
        }
        $this->headers[] = $column;
        return $this;
    }
    
	/*
		Apply a transformation callback for one or more columns when
		outputing the DataFrame. If no columns are specified then the 
		operation applies to all. 
	
		You might use this to format timestamps into dates or to unify
		the display of currency.
	
		The callback should return the formatted value as it should be
		displayed.
	
		This method does not modify the original value within the Dataframe.
	
		Callback format: myFunc($value)
	*/
    public function apply_display_transformer($callback, ...$columns)
    {
        $columns = $this->determineColumns($columns);
        foreach ($columns as $column) {
            $this->transformers[$column] = $callback;
        }
        return $this;
    }
    
	/*
		Produce a plot object (from the plotlib module) auto-configured
		to create an image-based graph of one or more columns.
	
		$options represent the chart configuation.
			- title: 		Filename of the chart. Defaults to the chart type 
					 		and series being plotted.
			- columns: 		Array of the column names to produce charts for.
			- xcolumn: 		A column name to use as the x-axis.
			- one: 			If TRUE then all columns will be rendered onto one chart. 
				   			When FALSE multiple charts are generated.
			- min:			The minimum Y-value to render.
			- max:			The maximum Y-value to render.
			- lines:		Array of infinite lines to be drawn onto the chart. Each 
							item in the array is an associative array containing the 
							the following options:
							- direction: Either VERTICAL or HORIZONTAL.
							- value: the numerical position on the respective axis that
								 the line will be rendered.
							- color: a colour name (e.g. red, blue etc) for the line 
								 colour. Default is red.
							- width: the stroke width of the line, default is 1.
			- labelangle:	Angular rotation of the x-axis labels, default is 0.	
			- bars:			A liniar array of values to represent an auxiliary/background
							bar chart dataset. This will plot on it's own Y axis.
			- barColor:		The colour of the bars dataset, default is 'lightgray'.
			- barWidth:		The width of each bar in the bars dataset, default is 7.
							
		$type represents the type of chart (e.g line, box, bar etc). Possible values:
				- line: 		line chart.
				- linefill: 	line chart with filled area.
				- bar:			bar chart.
				- barstacked:	bar chart with each series stacked atop for each
								data point.
				- scatter:		scatter chart.
				- box:			Similar to a stock plot but with a fifth median value.
	
		See: plotlib for possibly more information.
	*/
    public function plot(string $type, array $options = [])
    {
        $columns = $this->determineColumns(arrays::safe_value($options, 'columns'));
        $title = arrays::safe_value($options, 'title', '');
        $xcolumn = arrays::safe_value($options, 'xcolumn', null);
        $oneChart = arrays::safe_value($options, 'one', false);
        
        $plot = new BulkPlot($title);
        if ($oneChart)
            $all_series = [];
        
        $xseries = null;
        $xtr = null; 
        if ($xcolumn)
        {
            foreach ($this->headers as $h) {
                if ($h == $xcolumn) {
                    $xseries = $this->values($h);
                    break;
                }
            }
            $xtr = $this->transformers[$xcolumn] ?? null;
        }
        else
        {
            $xseries = array_keys($this->data);
            $xtr = $this->transformers[$this->indexHeader] ?? null;
        }

        foreach ($columns as $i => $h)
        {
            if ($xcolumn === null or ($xcolumn !== null && $h != $xcolumn)) {
                $values = $this->values($h);
				$ytr = $this->transformers[$h] ?? null;
			
                if ($oneChart)
                    $all_series[] = $values;     
                else {
                    $plot->add($type, [$values], array_merge($options, [
                        'xseries' => $xseries, 'xformatter' => $xtr,
                        'legend' => $h, 'yformatter' => $ytr
                    ])); 
                }
            }
        }
    
        if ($oneChart)
            $plot->add($type, $all_series, array_merge($options, ['xseries' => $xseries, 'xformatter' => $xtr,
                 'legend' => $columns]));
		       
        
        return $plot;
    }
	
	/*
		Produce a candle-stick chart, typically used for tracking stock prices.
	
		You must specify exactly 4 columns.
	
		$options can include a 'volume' key, specifying an associative array with
		the subkeys 'key', 'color' and 'width' for representing volume as a
		background bar chart.
	
		All other standard option keys can be passed in.
	*/
	public function stock(string $openP, string $closeP, string $lowP, string $highP, array $options = [])
	{
		$series = [ $this->matrix($openP, $closeP, $lowP, $highP) ];
		
        $title = arrays::safe_value($options, 'title');
        $xcolumn = arrays::safe_value($options, 'xcolumn', null);
		if (isset($options['volume'])) {
			$options['bars'] = $this->values($options['volume']['key'], false);
			if (isset($options['volume']['color']))
				$options['barColor'] = $options['volume']['color'];
			if (isset($options['volume']['width']))
				$options['barWidth'] = $options['volume']['width'];
			if (isset($options['volume']['legend']))
				$options['barLegend'] = $options['volume']['legend'];
		}
		
        if ($xcolumn)
        {
            $xseries = $this->values($xcolumn);
            $xtr = $this->transformers[$xcolumn] ?? null;
        }
        else
        {
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
    
	/*
		Create a box plot chart, which is a singular data point of box-like
		appearance that illustrates the place of the 25%, 50% and 75% quartiles
		as well as the outer whiskers.
	*/
    public function box(...$columns)
    {
        $columns = $this->determineColumns($columns);
        $plot = new BulkPlot('box');
        
        foreach ($columns as $h)
        {
            $q25 = $this->quartile(0.25, $h);
            $q50 = $this->quartile(0.50, $h);
            $q75 = $this->quartile(0.75, $h);
            $whisker = ($q75-$q25) * 1.5;
            $series = [$q25, $q75, $q25-$whisker, $q75+$whisker, $q50];
            
            $plot->add('box', [$series], ['legend' => $h, 'hideAllTicks' => true]);
        }
        
        return $plot;
    }
    
	/*
		Create a bar chart styled in the fashion of a histogram.
	*/
    public function hist(array $options = [])
    {
        $columns = $this->determineColumns(arrays::safe_value($options, 'columns'));
        $bins = arrays::safe_value($options, 'bins', 10);
        $dlow = arrays::safe_value($options, 'low', null);
        $dhigh = arrays::safe_value($options, 'high', null);
        $is_cumulative = arrays::safe_value($options, 'cumulative', false);
        
        $b_array = null;
        if (is_array($bins)) {
            $b_array = $bins;
            $bins = count($b_array)-1;
        }
        
        $plot = new BulkPlot('hist');
        
        foreach ($columns as $h)
        {
            $values = $this->values($h);
            $min = ($dlow !== null) ? $dlow : floor(min($values));
            $max = ($dhigh !== null) ? $dhigh : ceil(max($values));
            $delta = ($max - $min) / $bins;
            
            $processed = [];
            $dist = [];
            $dist2 = [];
            $cumm = 0;
            for ($i = 0; $i < $bins; $i++) 
            {
                $lowBin = $b_array ? $b_array[$i] : $min + $i * $delta;
                $hiBin = $b_array ? $b_array[$i+1] : $lowBin + $delta;
                $tally = 0;
                $vcount = count($values);
                
                for ($j = 0; $j < $vcount; $j++)
                {
                    if (arrays::contains($processed, $j))
                        continue;
                    
                    if ($j == $vcount-1)
                        $inRange = ($values[$j] >= $lowBin && $values[$j] <= $hiBin);
                    else
                        $inRange = ($values[$j] >= $lowBin && $values[$j] < $hiBin);
                    
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
                ]); 
            }
            else {
                $plot->add('bar', [$dist], [
                    'legend' => $h,
                    'matchBorder' => true,
                    'width' => 1.0,
                ]); 
            }
            
        }
        
        return $plot;
    }
    
	/*
		Export the Dataframe to a delimetered text file (CSV).
	
		$filePath: The destination file.
		$columns: The columns to export, or all if null is supplied.
		$delimeter: The character that seperates each column.
	*/
    public function export(string $filePath, array $columns = null, string $delimeter = ',')
    {
        $columns = $this->determineColumns($columns);
		
		$fh = fopen($filePath, 'w+');
		defer ($_, function() use ($fh) {
			if ($fh)
				fclose($fh);
		});
       	
        if ($this->showHeaders) {
            $headers = array_merge([''], $columns);
            fputcsv($fh, $headers, $delimeter);
        }

        foreach ($this->data as $index => $row)
        {
            $index = ($this->showGenericIndexes || ! is_int($index)) ? $index : '';
            if ($index && $this->indexHeader && isset($this->transformers[$this->indexHeader])) {
                $tr = $this->transformers[$this->indexHeader];
                $index = $tr($index);
            }
            $out = [$index];
            foreach ($columns as $h) {
                $value = $row[$h] ?? '';
                if ($value && isset($this->transformers[$h])) {
                    $tr = $this->transformers[$h];
                    $value = $tr($value);
                }
                $out[] = $value;
            }
                
            fputcsv($fh, $out, $delimeter);
        }
    }
}  