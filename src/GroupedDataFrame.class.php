<?php
namespace sqonk\phext\datakit;

use sqonk\phext\core\arrays;

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
 * The GroupedDataFrame is a special class that manages a group of
 * normal DataFrame objects. Normal actions on the DataFrame can be
 * called and actioned against all objects within the set.
 * 
 * This class is used internally by DataFrame and you should not
 * need to instantiate it yourself under most conditions.
 * 
 * @implements \IteratorAggregate<list<array<string, string>>>
 * @implements \ArrayAccess<list<array<string, string>>>
 */
final class GroupedDataFrame implements \Countable, \IteratorAggregate, \ArrayAccess
{
	/**
	 * The collection of DataFrames.
	 * 
	 * @var list<array<string, string>>
	 */
    protected array $sets;
	
	// The column the different frames were split by.
    protected string $column;
	
	// -------- Class Interfaces
	
	public function getIterator(): \Iterator {
		return new \ArrayIterator($this->sets);
	}
	
	public function offsetSet(mixed $index, mixed $dataFrame): void
	{
		if (! $dataFrame instanceof DataFrame)
			throw new \Exception('Only DataFrames can be added to the set of a GroupedDataFrame. Null or incorrect object type given.');
		
		if ($index === null)
			$this->sets[] = $dataFrame;
		else
			$this->sets[$index] = $dataFrame;
	}
	
	public function offsetExists(mixed $index): bool
	{
		return isset($this->sets[$index]);
	}
	
	public function offsetUnset(mixed $index): void
	{
		if (isset($this->sets[$index])) {
			$this->sets[$index] = null;
			$this->sets = arrays::compact($this->sets);
		}
	}
	
	public function offsetGet(mixed $index): mixed
	{
		return $this->sets[$index] ?? null;
	}
	
	public function count(): int {
		return count($this->sets);
	}
	
	// -------- Main class methods
    
    /**
     * Construct a new GroupedDataFrame containing multiple DataFrame objects.
     * 
     * -- parameters:
     * @param list<array<string, string>> $groups Array of standard DataFrame objects.
     * @param string $groupedColumn The singular DataFrame column that was used to split the original frame into the group.
     */
    public function __construct(array $groups, string $groupedColumn)
    {
        $this->sets = $groups;
        $this->column = $groupedColumn;
    }
    
    /**
     * @param string $name
     * @param array<mixed> $args
     */
    public function __call(string $name, array $args): mixed
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
    
    public function __get(mixed $key): array {
        return $this->sets[$key];
    }
    
	// Conversion to string will run a report on each frame within the group.
    public function __tostring(): string
    {
        $out = [];
        foreach ($this->sets as $df)
            $out[] = $df->report();
        return implode("\n", $out);
    }
	
	
    
    /**
     * Combine all frames within the group back into a singular DataFrame.
     * 
     * If $keepIndexes is set to true then all existing indexes are kept and
     * merged. Keep in mind that you may suffer data overwrite if one or more
     * of the frames in the set have matching indexes.
     * 
     * -- parameters:
     * @param bool $keepIndexes  When set to FALSE then the new DataFrame reindexes all rows with a standard numerical sequence starting from 0.
     * 
     * @return DataFrame the new combined DataFrame.
     */
    public function combine(bool $keepIndexes = true): DataFrame
    {
        $combined = [];
        foreach ($this->sets as $df) {
            $data = $keepIndexes ? $df->data() : array_values($df->data());
            $combined = array_merge($combined, $data);   
        }
        $keys = array_keys($this->sets);
        $first = $this->sets[$keys[0]];
        $df = new DataFrame($combined); 
        $df->transformers($first->transformers());
        $df->index($first->index());
        $df->display_generic_indexes($first->display_generic_indexes());
        $df->display_headers($first->display_headers());
        return $df;
    }
    
	/**
	 * Functional map to the standard export within DataFrame.
	 * 
	 * -- parameters:
	 * @param string $dir Path to the directory/folder to export the CSV to.
	 * @param list<string> $columns Which columns to export.
	 * @param string $delimiter CSV delimiter.
	 * 
	 * @return ?list<array<string, string>> 
	 */
    public function export(string $dir = '.', array $columns = null, string $delimeter = ','): ?array   
    {
        if (php_sapi_name() == 'cli' && $dir !== null && ! file_exists($dir))
            mkdir($dir, 0777, true);
        
        if ($dir === null) {
            $out = [];
            foreach ($this->sets as $df) {
                ob_start();
                $df->export("php://output", $columns, $delimiter);
                $out[] = ob_get_contents();
                ob_end_clean();
            }
            return $out;
        }
            
        foreach ($this->sets as $df) {
            $id = $df->values($this->column)[0];
            $df->export("$dir/$id.csv", $columns, $delimeter);
        }
        return null;
    }
}