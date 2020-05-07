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

/*
	The GroupedDataFrame is a special class that manages a group of 
	normal DataFrame objects. Normal actions on the DataFrame can be
	called and actioned against all objects within the set.

	This class is used internally by DataFrame and you should not
	need to instanciate it yourself under most conditions.
*/

use sqonk\phext\core\arrays;

class GroupedDataFrame implements \Countable, \IteratorAggregate, \ArrayAccess
{
	// The collection of DataFrames.
    protected $sets;
	
	// The column the different frames were split by.
    protected $column;
	
	// -------- Class Interfaces
	
	public function getIterator()
	{
		return new \ArrayIterator($this->sets);
	}
	
	public function offsetSet($index, $dataFrame)
	{
		if (! $dataFrame instanceof DataFrame)
			throw new \IllegalArguementException('Only DataFrames can be added to the set of a GroupedDataFrame. Null or incorrect object type given.');
		
		if ($index === null)
			$this->sets[] = $dataFrame;
		else
			$this->sets[$index] = $dataFrame;
	}
	
	public function offsetExists($index)
	{
		return isset($this->sets[$index]);
	}
	
	public function offsetUnset($index)
	{
		if (isset($this->sets[$index])) {
			$this->sets[$index] = null;
			$this->sets = arrays::compact($this->sets);
		}
	}
	
	public function offsetGet($index)
	{
		return $this->sets[$index] ?? null;
	}
	
	public function count()
	{
		return count($this->sets);
	}
	
	// -------- Main class methods
    
    /*
        Construct a new GroupedDataFrame containing multiple DataFrame objects.
    
        The $groupedColumn maintains a record of the singular DataFrame column
        that was used to split the original frame.
    */
    public function __construct(array $groups, string $groupedColumn)
    {
        $this->sets = $groups;
        $this->column = $groupedColumn;
    }
    
    public function __call(string $name, array $args)
    {
        $result = [];
        foreach ($this->sets as $df) {
            $r = call_user_func([$df, $name], ...$args);
			if ($r)
				$result[] = $r;
        }
        
        if (count($result) > 0 and $result[0] instanceof DataFrame) 
        {
            $orig = array_keys($this->sets);
            for ($i = 0; $i < count($result); $i++) 
            {
                $df = $result[$i];
                $data = $df->data();
                $keys = array_keys($data);
                
                if (count($data) == 1 && $keys[0] == 0) {
                    $df->reindex_rows([ $orig[$i] ], true); 
                }
                    
            }
            return new GroupedDataFrame($result, $this->column);
        }
        else
            return $result;
    }
    
    public function __get($key)
    {
        return $this->sets[$key];
    }
    
	// Conversion to string will run a report on each frame within the group.
    public function __toString()
    {
        $out = [];
        foreach ($this->sets as $df)
            $out[] = $df->report();
        return implode("\n", $out);
    }
	
	
    
	/* 
		Combine all frames within the group back into a singular DataFrame.
		
		If $keepIndexes is set to true then all existing indexes are kept and
		merged. Keep in mind that you may suffer data overwrite if one or more
		of the frames in the set have matching indexes.
	
		If $keepIndexes is set to false then the new DataFrame reindexes all rows
		with a standard numerical sequence starting from 0.
	
		Returns the new combined DataFrame.
	*/
    public function combine(bool $keepIndexes = true)
    {
        $combined = [];
        foreach ($this->sets as $df) {
            $data = $keepIndexes ? $df->data() : array_values($df->data());
            $combined = array_merge($combined, $data);   
        }
        $df = new DataFrame($combined);
        $df->transformers($this->sets[0]->transformers());
        $df->index($this->sets[0]->index());
        $df->display_generic_indexes($this->sets[0]->display_generic_indexes());
        $df->display_headers($this->sets[0]->display_headers());
        return $df;
    }
    
	// Functional map to the standard export within DataFrame.
    public function export($dir = '.', array $columns = null, string $delimeter = ',')   
    {
        if (php_sapi_name() == 'cli' && $dir !== null && ! file_exists($dir))
            mkdir($dir, 0777, true);
        
        if ($dir === null) {
            $out = [];
            foreach ($this->sets as $df)
                $out[] = $df->export("php://output", $columns, $delimeter);
            return $out;
        }
        else {
            foreach ($this->sets as $df) {
                $id = $df->values($this->column)[0];
                $df->export("$dir/$id.csv", $columns, $delimeter);
            }
        }
    }
}