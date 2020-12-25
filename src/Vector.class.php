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

use sqonk\phext\core\{arrays,strings};

/**
 * A class to add both object orientation and utility methods to
 * native arrays, enabling easier to write and easier to read code.
 * 
 * In particular it sports a variety of basic mathematical and
 * statistical functions.
 */
final class Vector implements \ArrayAccess, \Countable, \IteratorAggregate
{
	// The internal native array used to store the data.
	protected $_array;
	
	// When the keys of the array are known to run in numerical order,
	// starting from 0, this will be set to TRUE.
	protected $isSequential;
	
	// User defined arbitrary size limit the array.
	protected $constraint;
		
	// -------- Class Interfaces
	
	public function getIterator()
	{
		return new \ArrayIterator($this->_array);
	}
	
	public function offsetSet($index, $value)
	{
		if ($index === null)
			$this->add($value);
		else
			$this->set($index, $value);
	}
	
	public function offsetGet($index)
	{
		return $this->get($index);
	}
	
	public function offsetExists($index)
	{
		return array_key_exists($index, $this->_array);
	}
	
	public function offsetUnset($index)
	{
		$this->remove($index);
	}

	public function __toString()
	{
		return var_export($this->_array, true);
	}
	
	// ------- Main class methods
	
    /**
     * Contruct a new vector with the provided array.
     */
	public function __construct(array $startingArray = [])
	{
		$this->_array = $startingArray;
		$this->testKeys();
	}
	
	// Internal.
	// Test whether the array is sequential or associative.
	protected function testKeys()
	{
		$this->isSequential = true;
		foreach (array_keys($this->_array) as $key)
		{
			if (! is_int($key)) {
				$this->isSequential = false;
				break;
			}
		}
		return $this->isSequential;
	}
	
	// Returns a copy of the vector as a native array.
	public function array()
	{
		return $this->_array;
	}
	
	/**
	 * Return the number of elements in the array.
	 */
	public function count()
	{
		return count($this->_array);
	}
	
    /**
    * Set a rolling capacity limit on the vector. Once set, old values
    * will be shifted off of the beginning to make room for new
    * values once the capacity is reached.
    * 
    * Setting the limit to NULL will remove the constraint altogether,
    * which is the default.
    */
	public function constrain(?int $limit): Vector
	{
		$this->constraint = $limit;
		return $this;
	}
	
	/**
	 * Add one or more elements to the end of the vector.
	 */
	public function add(...$values): Vector
	{
		foreach ($values as $value)
			$this->_array[] = $value;
		
		if ($this->constraint !== null) {
			$diff = $this->count() - $this->constraint;
			if ($diff > 0)
				$this->shift($diff);
		}
		
		return $this;
	}
	
    /**
     * Set an element in the array to the provided key/index.
     */
	public function set($key, $value): Vector
	{
		$isEmpty = $this->empty();
		
		$this->_array[$key] = $value;
		
		if ($this->isSequential and ! is_int($key)) 
			$this->isSequential = false;
		
		if ($this->constraint !== null) {
			$diff = $this->count() - $this->constraint;
			if ($diff > 0)
				$this->shift($diff);
		}
		
		return $this;
	}
	
    /**
     * Add one or more elements to the start of the vector. If a constraint
     * is set then excess elements will be removed from the end.
     */
	public function prepend(...$values): Vector
	{
		array_unshift($this->_array, ...$values);
		
		if ($this->constraint !== null) {
			$diff = $this->count() - $this->constraint;
			if ($diff > 0)
				$this->pop($diff);
		}
		
		return $this;	
	}
	
    /**
     * Add a value supplied by the callback to the end of the vector a set
     * number of times.
     * 
     * The callback should take no parameters.
     */
	public function fill(int $amount, callable $callback): Vector
	{
		foreach (sequence(0, $amount-1) as $i) {
			$this->add($callback($i));
		}
		
		return $this;
	}
	
    /**
     * Add a value supplied by the callback to the start of the vector a set
     * number of times.
     * 
     * The callback should take no parameters.
     */
	public function prefill(int $amount, callable $callback): Vector
	{
		foreach (sequence(0, $amount-1) as $i) {
			$this->prepend($callback($i));
		}
		
		return $this;
	}
	
    /**
     * Return the value for a specified key. If the key is not present in the array then 
     * the default value is returned instead.
     * 
     * You may optionally pass a callback as the $key. When you do this the callback is used
     * as a filter, where by the first item the callback returns TRUE for will be returned
     * by the function as the found object.
     * 
     * Callback format: `myFunc($value, $index) -> bool`
     * 
     * @return The found item or NULL if nothing was located for the key.
     */
	public function get($key, $default = null)
	{
        if (is_callable($key) && ! is_string($key))
        {
            foreach ($this->_array as $index => $item) {
                if ($key($item, $index))
                    return $item;
            }
            return null;
        }
		return $this->_array[$key] ?? $default;
	}
	
    /**
     * Remove one or more elements from the vector.
     */
	public function remove(...$keys): Vector
	{
		$modified = false;
		foreach ($keys as $key) {
			if (array_key_exists($key, $this->_array)) {
				unset($this->_array[$key]);
				$modified = true;
			}
		}
		if ($modified and $this->isSequential)
			$this->_array = $this->_values(); // re-index the array.
		
		return $this;
	}
    
    /**
     * Remove a range of values from the vector from the index at $start and
     * extending for $length.
     * 
     * This method is primarily designed to work with sequential indexes but
     * will also work with associative arrays by running the start and length
     * through the extracted array keys.
     */
    public function remove_range(int $start, int $length): Vector
    {
        $keys = array_keys($this->_array);
        if ($start + $length > count($keys)-1)
            throw new \InvalidArgumentException('range exceeds length of array.');
        if ($length < 1 or $start < 1)
            throw new \InvalidArgumentException('start and length must be 1 or greater.');
        
        foreach (sequence($start, $start+$length-1) as $key) {
            unset($this->_array[$key]);
        }
        
        if ($this->isSequential)
            $this->_array = $this->_values(); // re-index the array.
        
        return $this;
    }
	
	/**
	 * Remove all elements from the array.
	 */
	public function clear(): Vector
	{
		unset($this->_array);
		$this->_array = [];
        
        return $this;
	}
	
    /**
     * Returns TRUE if all the specified keys are present within the vector, FALSE
     * otherwise.
     */
	public function isset(...$keys): bool
	{
		$vkeys = new Vector(array_keys($this->_array));
		foreach ($keys as $key) {
			if (! $vkeys->any($key))
				return false;
		}
		
		return true;
	}
	
	/**
	 * Return all indexes of the array.
	 */
	public function keys(): Vector
	{
		return new Vector(array_keys($this->_array));
	}
	
	/**
	 * Returns TRUE if there are 0 elements in the array, FALSE otherwise.
	 */
	public function empty(): bool
	{
		return $this->count() == 0;
	}
	
    /**
     * Return all values for a given key in the vector. This assumes all elements
     * inside of the vector are an array or object.
     * 
     * If no key is provided then it will return all primary values in the vector.
     */
    public function values($key = null): Vector
	{
		return new Vector($this->_values($key));
	}
	
    /**
     * *** Internal base method that returns a native array.
     * 
     * Return all values for a given key in the vector. This assumes all elements
     * inside of the vector are an array or object.
     * 
     * If no key is provided then it will return all primary values in the vector.
     * 
     * @return a native PHP array of the correpsonding values.
     */
    protected function _values($key = null)
	{
		if ($key === null)
			return array_values($this->_array);
		
		return array_column($this->_array, $key);
	}

    /**
     * Return a new vector containing all unique values in the current.
     * 
     * If $key is provided then the operation is performed on the values resulting
     * from looking up $key on each element in the vector. This assumes all elements
     * inside of the vector are an array or object.
     */
	public function unique($key = null): Vector
	{
		if ($key === null)
			$out = $this->_array;
		
		else
			$out = $this->_values($key);
		
		return new Vector(array_unique($out));
	}
	
    /**
     * Produces a new vector containing counts for the number of times each value
     * occurs in the array.
     * 
     * If $key is provided then the operation is performed on the values resulting
     * from looking up $key on each element in the vector, assuming all elements
     * inside of the vector are an array or object.
     */
	public function frequency($key = null): Vector
	{
		if ($key === null)
			$out = $this->_array;
		
		else
			$out = $this->_values($key);
		
		return new Vector(array_count_values($out));
	}
	
    /**
     * Remove all entries where the values corresponding to 'empties' are omitted.
     */
    public function prune($empties = ''): Vector
    {
		$modified = false;
        foreach ($this->_array as $key => $value) { 
            if ($value === $empties) {
            	$modified = true;
				unset($this->_array[$key]);
            }
        }
		if ($modified and $this->isSequential)
			$this->_array = $this->_values(); // re-index the array.
        
        return $this;
    }
	
	/**
	 * Return the first object in the array or null if array is empty.
	 */
	public function first()
	{
		return arrays::first($this->_array);
	}
	
	/**
	 * Return the last object in the array or null if array is empty.
	 */
	public function last()
	{
		return arrays::end($this->_array);
	}
	
    /**
     * Return the object closest to the middle of the array.
     * 
     * - If the array is empty, returns NULL.
     * 
     * - If the array has less than 3 items, then return the first or last item depending
     * on the value of $weightedToFront.
     * 
     * - Otherwise return the object closest to the centre. When dealing with arrays containing
     * an even number of items then it will use the value of $weightedToFront to determine if it
     * picks the item closer to the start or closer to the end.
     * 
     * -- parameters:
     * @param $array The array containing the items.
     * @param $weightedToFront TRUE to favour centre items closer to the start of the array and FALSE to prefer items closer to the end.
     */
	public function middle(bool $weightedToFront = true)
	{
		return arrays::middle($this->_array, $weightedToFront);
	}
	
    /**
     * Randomly choose and return an item from the vector.
     */
    public function choose()
	{
		return arrays::choose($this->_array);
	}
	
    /**
     * Returns the first item in the vector found in the heystack or FALSE if none are found.
     */
	public function occurs_in(string $heystack)
	{
		foreach ($this->_array as $value)
			if (strings::contains($heystack, $value))
				return $value;
		
		return false;
	}
	
    /**
     * Returns TRUE if any of the values within the vector are equal to the value
     * provided, FALSE otherwise.
     * 
     * A callback may be provided as the match to perform more complex testing.
     * 
     * Callback format: `myFunc($value) -> bool`
     * 
     * For basic (non-callback) matches, setting $strict to TRUE will enforce
     * type-safe comparisons.
     */
	public function any($match, bool $strict = false): bool
	{
		if (is_callable($match))
		{
			foreach ($this->_array as $value) {
				if ($match($value))
					return true;
			}
			return false;
		}
		
		return in_array($match, $this->_array, $strict);
	}
	
    /**
     * Returns TRUE if all of the values within the vector are equal to the value
     * provided, FALSE otherwise.
     * 
     * A callback may be provided as the match to perform more complex testing.
     * 
     * Callback format: `myFunc($value) -> bool`
     * 
     * For basic (non-callback) matches, setting $strict to TRUE will enforce
     * type-safe comparisons.
     */
	public function all($match, bool $strict = false): bool
	{
		$isCallback = is_callable($match);
		foreach ($this->_array as $value) {
			if (($isCallback and ! $match($value)) or 
				(! $isCallback and (! $strict && $value != $match) or ($strict && $value !== $match)))
				return false;
		}
		return true;
	}
	
    /**
     * Filter the contents of the vector using the provided callback.
     * 
     * `ARRAY_FILTER_USE_BOTH` is provided as the flag to array_filter() so that
     * your callback may optionally take the key as the second parameter.
     */
	public function filter(callable $callback): Vector
	{
		return new Vector(array_filter($this->_array, $callback, ARRAY_FILTER_USE_BOTH));
	}
	
    /**
     * Filter the vector based on the contents of one or more vectors or arrays and return a
     * new vector containing just the elements that were deemed to exist in all.
     */
    public function intersect(...$otherArrays): Vector
	{
		$adjusted = [];
		foreach ($otherArrays as $index => $array) {
			if ($array instanceof Vector)
				$adjusted[$index] = $array->array();
			else if (is_array($array))
				$adjusted[$index] = $array;
			else
				throw new \InvalidArgumentException('Only native arrays or vectors are valid parameters for interect().');
		}
		return new Vector(array_intersect($this->_array, ...$adjusted));
	}
	
    /**
     * Filter the vector based on the contents of one or more arrays and return a
     * new vector containing just the elements that were deemed not to be present
     * in all.
     */
    public function diff(...$otherArrays): Vector
	{
		$adjusted = [];
		foreach ($otherArrays as $index => $array) {
			if ($array instanceof Vector)
				$adjusted[$index] = $array->array();
			else if (is_array($array))
				$adjusted[$index] = $array;
			else
				throw new \InvalidArgumentException('Only native arrays or vectors are valid parameters for diff().');
		}
		return new Vector(array_diff($this->_array, ...$adjusted));
	}
	
    /**
     * Return a copy of the vector containing only the values for the specified keys,
     * with index association being maintained.
     * 
     * This method is primarily designed for non-sequential vectors but can also be used
     * with sequential 2-dimensional vectors. If the vector is sequential and the elements
     * contained within are arrays or vectors then the operation is performed on them,
     * otherwise it is performed on the top level of the vector.
     * 
     * It should be noted that if a key is not  present in the current vector then it will
     * not be present in the resulting vector.
     */
	public function only_keys(...$keys): Vector
	{
		if ($this->isSequential) 
		{	
			return $this->map(function($element) use ($keys) {
				if (is_array($element))
					return arrays::only_keys($element, ...$keys);
				
				else if ($element instanceof Vector)
					return $element->only_keys(...$keys);
				
				return $element;
			});
		}
		return new Vector(arrays::only_keys($this->_array, ...$keys));
	}
	
    /**
     * Search the array for the given needle (subject). This function is an
     * alias of Vector::any().
     */
    public function contains($needle): bool
    {
        return self::any($needle);
    }
    
    /**
     * Determines if the array ends with the needle.
     */
    public function ends_with($needle): bool
    {
        return arrays::ends_with($this->_array, $needle);
    }
    
    /**
     * Determines if the array starts with the needle.
     */
    public function starts_with($needle): bool
    {
        return arrays::starts_with($this->_array, $needle);
    }
    
    /**
     * Trim all entries in the array (assumes all entries are strings).
     */
    public function trim(): Vector
    {
        return new Vector(array_map('trim', $this->_array));
    }
	
    /**
     * Join all elements in the vector into a string using the supplied delimier
     * as the seperator.
     * 
     * This assumes all elements in the vector are capable of being cast to a
     * string.
     */
	public function implode(string $delimier = '', string $subDelimiter = ''): string
	{
		$transformed = $this->map(function($element) use ($subDelimiter) {
			if (is_array($element)) 
				return arrays::implode($subDelimiter, $element);
			
			else if ($element instanceof Vector)
				return $element->implode($subDelimiter);
			
			return $element;
		});
		
		return implode($delimier, $transformed->array());
	}
	
    /**
     * Implode the vector using the desired delimiter and subdelimiter.
     * 
     * This method is primarily intended for non-senquential/associative vectors
     * and differs from the standard implode in that it will only implode the values
     * associated with the specified keys/indexes.
     */
	public function implode_only(string $delimier, array $keys, string $subDelimiter = ''): string
	{
		return $this->only_keys(...$keys)->implode($delimier, $subDelimiter);
	}

    /**
     * Apply a callback function to the vector. This version will optionally
     * supply the corresponding index/key of the value when needed.
     * 
     * Callback format: `myFunc($value, $index) -> mixed`
     */
	public function map(callable $callback): Vector
	{
		return new Vector(arrays::map($this->_array, $callback));
	}
	
    /**
     * Split the array into batches each containing a total specified
     * by $itemsPerBatch.
     * 
     * The final batch may contain less than the specified batch count if
     * the array total does not divide evenly.
     */
	public function chunk(int $itemsPerBatch): Vector
	{
		return new Vector(array_map(function($v) {
			return new Vector($v);
		}, array_chunk($this->_array, $itemsPerBatch, ! $this->isSequential)));
	}
	
    /**
     * Pad vector to the specified length with a value. If $count is positive then
     * the array is padded on the right, if it's negative then on the left. If the
     * absolute value of $count is less than or equal to the length of the array
     * then no padding takes place.
     */
	public function pad(int $count, $value): Vector
	{
		$this->_array = array_pad($this->_array, $count, $value);
		
		return $this;
	}
	
    /**
     * Shorten the vector by removing elements off the end of the array to the number
     * specified in $amount. If $returnRemoved is TRUE then the items removed will
     * be returned, otherwise it returns a reference to itself for chaining purposes.
     */
    public function pop(int $amount = 1, bool $returnRemoved = false): Vector
    {
        if ($this->count() == 0)
            throw new \Exception('Tried to pop a vector that has no elements.');
        
		$r = $returnRemoved ? $this->tail($amount) : $this;
		
		foreach (sequence(1, $amount) as $i)
			array_pop($this->_array);
		
		return $r;
    }
    
    /**
     * Modify the vector by removing elements off the beginning of the array to the
     * number specified in $amount and return a vector containing the items removed.
     * If $returnRemoved is TRUE then the items removed will be returned, otherwise
     * it returns a reference to itself for chaining purposes
     */
    public function shift(int $amount = 1, bool $returnRemoved = false): Vector
    {
        if ($this->count() == 0)
            throw new \Exception('Tried to shift a vector that has no elements.');
        
		$r = $returnRemoved ? $this->head($amount) : $this;
		
		foreach (sequence(1, $amount) as $i)
			array_shift($this->_array);
		
		return $r;
    }
	
    /**
     * Transform a set of rows and columns with vertical data into a horizontal configuration
     * where the resulting array contains a column for each different value for the given
     * fields in the merge map (associative array).
     * 
     * The group key is used to specifiy which field in the array will be used to flatten
     * multiple rows into one.
     * 
     * For example, if you had a result set that contained a 'type' field, a corresponding
     * 'reading' field and a 'time' field (used as the group key) then this method would
     * merge all rows containing the same time value into a matrix containing as
     * many columns as there are differing values for the type field, with each column
     * containing the corresponding value from the 'reading' field.
     */
    public function transpose(string $groupKey, array $mergeMap): Vector
	{
		$this->keyed_sort($groupKey);
		return new Vector(arrays::transpose($this->_array, $groupKey, $mergeMap));
	}
	
    /**
     * Transfom the vector (assuming it is a flat array of elements) and split them into a
     * tree of vectors based on the keys passed in.
     * 
     * The vector will be re-sorted by the same order as the set of keys being used. If only
     * one key is required to split the array then a singular string may be provided, otherwise
     * pass in an array.
     * 
     * Unless $keepEmptyKeys is set to TRUE then any key values that are empty will be omitted.
     */
	public function groupby($keys, bool $keepEmptyKeys = false): Vector
	{
		$this->keyed_sort($keys);
		$r = arrays::group_by($this->_array, $keys, $keepEmptyKeys);
		
		return new Vector(arrays::map($r, function($subarray) {
			return new Vector($subarray);
		}));
	}
	
    /**
     * Sort the vector in either `ASCENDING` or `DESCENDING` direction. If the
     * vector is associative then index association is maintained, otherwise
     * new indexes are generated.
     * 
     * Refer to the PHP documentation for all possible values on the $flags.
     */
	public function sort(bool $dir = ASCENDING, int $flags = SORT_REGULAR): Vector
	{
		if ($this->isSequential) {
			if ($dir == ASCENDING) 
				sort($this->_array, $flags);
			else
				rsort($this->_array, $flags);
		}
		else {
			if ($dir == ASCENDING) 
				asort($this->_array, $flags);
			else
				arsort($this->_array, $flags);
		}
		return $this;
	}
    
    /**
     * Sort the vector by the indexes in either `ASCENDING` or `DESCENDING` direction.
     * 
     * Refer to the PHP documentation for all possible values on the $flags.
     */
	public function ksort(bool $dir = ASCENDING, int $flags = SORT_REGULAR): Vector
	{
		if ($dir == ASCENDING) 
			ksort($this->_array, $flags);
		else
			krsort($this->_array, $flags);
        
		return $this;
	}
	
    /**
     * Sort the vector based on the value of a key inside of the sub-array/object.
     * 
     * $key can be a singular string, specifying one key, or an array of keys.
     * 
     * If the vector is associative then index association is maintained, otherwise new
     * indexes are generated.
     * 
     * NOTE: This method is designed for multi-dimensional vectors or vectors of objects.
     * 
     * See ksort for sorting the vector based on the array indexes.
     */
	public function keyed_sort($key): Vector
	{
		arrays::key_sort($this->_array, $key, ! $this->isSequential);
		
		return $this;
	}
    
    /**
     * Randomise the elements within the vector.
     */
    public function shuffle(): Vector
    {
        shuffle($this->_array);
        return $this;
    }
    
    /**
     * Treat the vector as a rotary collection and move each item back one place
     * in order. The item at the end will be moved to the front.
     * 
     * This method is designed for sequential arrays, indexes are not preserved.
     */
    public function rotate_back(): Vector
    {
        $item = array_pop($this->_array);
        $this->prepend($item);
        return $this;
    }
    
    /**
     * Alias of rotate_back()
     */
    public function rotate_right(): Vector
    {
        return $this->rotate_back();
    }
    
    /**
     * Treat the vector as a rotary collection and move each item forward one place
     * in order. The item at the front will be moved to the end.
     * 
     * This method is designed for sequential arrays, indexes are not preserved.
     */
    public function rotate_forward(): Vector
    {
        $item = array_shift($this->_array);
        $this->add($item);
        return $this;
    }
    
    /**
     * Alias of rotate_forward()
     */
    public function rotate_left(): Vector
    {
        return $this->rotate_forward();
    }
	
    /**
     * Return a copy of the vector only containing the number
     * of rows from the start as specified by $count.
     */
    public function head(int $count): Vector
    {
        if ($count >= $this->count())
            return new Vector($this->_array);
        
        return new Vector(array_slice($this->_array, 0, $count, ! $this->isSequential));
    }
    
    /**
     * Return a copy of the vector only containing the number
     * of rows from the end as specified by $count.
     */
    public function tail(int $count): Vector
    {
        $total = $this->count();
        if ($count >= $total)
            return new Vector($this->_array);
        
        return new Vector(array_slice($this->_array, $total-$count, $count, ! $this->isSequential));
    }
    
    /**
     * Return a copy of the vector only containing the the rows
     * starting from $start through to the given length.
     */
    public function slice(int $start, ?int $length = null): Vector
    {
        $total = count($this->_array);
        if ($start >= $total)
            throw new \InvalidArgumentException("Start of slice is greater than the length of the array.");
		
        if ($length and $start + $length > $total-1) 
            $length = null;
        
        return new Vector(array_slice($this->_array, $start, $length, ! $this->isSequential));
    }
    
    /**
     * Return a copy of the vector containing a random subset of the elements. The minimum and
     * maximum values can be supplied to focus the random sample to a more constrained subset.
     */
    public function sample(int $minimum, ?int $maximum = null): Vector
    {
        $count = $this->count();
        if ($maximum != null && $maximum < $count)
            $count = $maximum;
        
        $start = $count+1;
        while ($count-$start < $minimum)
            $start = rand(0, $count);
        
        $length = rand($minimum, $count-$start);
        return $this->slice($start, $length);
    }
	
    /**
     * Provide a maximum or minimum (or both) constraint for the values in the vector.
     * 
     * If a value exceeds that constraint then it will be set to the constraint.
     * 
     * If either the lower or upper constraint is not needed then passing in null will
     * ignore it.
     * 
     * If $inPlace is TRUE then this operation modifies this vector otherwise a copy is
     * returned.
     */
    public function clip($lower, $upper, bool $inplace = false)
    {
        if ($inplace)
        {
            foreach ($this->_array as $key => $value)
            {
                if ($lower !== null && is_numeric($value) && $value < $lower) 
                    $this->_array[$key] = $lower;
				
                else if ($upper !== null && is_numeric($value) && $value > $upper) 
                    $this->_array[$key] = $upper;
            }
            return $this;
        }
        else
        {
            return $this->map(function($value) use ($lower, $upper) 
			{
                if ($lower !== null && is_numeric($value) && $value < $lower) 
                    return $lower;
				
                else if ($upper !== null && is_numeric($value) && $value > $upper) 
                    return $upper;
				
				return $value;
			});
        }
    }
    
    /**
     * Reverse the current order of the values within the vector. If $inplace
     * is TRUE then this method will modify the existing vector instead of
     * returning a copy.
     */
    public function reverse(bool $inplace = false): Vector
    {
        if ($inplace) {
            $this->_array = array_reverse($this->_array);
            return $this;
        }
        return new Vector(array_reverse($this->_array));
    }
	
    /**
     * Swap the keys and values within the vector. If $inplace is TRUE then
     * this method will modify the existing vector instead of returning a
     * copy.
     */
	public function flip(bool $inplace = false): Vector
	{
		if ($inplace) {
			$this->_array = array_flip($this->_array);
			return $this;
		}
		return new Vector(array_flip($this->_array));
	}
	
    /**
     * Compute a sum of the values within the vector.
     */
    public function sum()
	{
		return array_sum($this->_array);
	}
	
    /**
     * Compute the average of the values within the vector.
     */
    public function avg()
    {
        return math::avg($this->_array);
    }
	
    /**
     * Return the maximum value present within the vector.
     */
    public function max()
    {
        if ($this->empty())
            return null;
        return max($this->_array);
    }
    
    /**
     * Return the minimum value present within the vector.
     */
    public function min()
    {
        if ($this->empty())
            return null;
        return min($this->_array);
    }
	
    /**
     * Find the median value within the vector.
     */
    public function median()
	{
		return math::median($this->_array);
	}
	
    /**
     * Compute the product of the values within the vector.
     */
    public function product()
	{
		return array_product($this->_array);
	}
	
    /**
     * Compute a cumulative sum of the values within the vector.
     */
    public function cumsum(): Vector
    {
        return new Vector(math::cumulative_sum($this->_array));
	}
	
    /**
     * Compute the cumulative maximum value within the vector.
     */
    public function cummax(): Vector
    {
        return new Vector(math::cumulative_max($this->_array));
	}
	
    /**
     * Compute the cumulative minimum value within the vector.
     */
    public function cummin(): Vector
    {
        return new Vector(math::cumulative_min($this->_array));
	}
	
    /**
     * Compute the cumulative product of the values within the vector.
     */
	public function cumproduct(): Vector
	{
		return new Vector(math::cumulative_prod($this->_array));
	}
	
    /**
     * Compute the variance of values within the vector.
     */
	public function variance()
	{
		return math::variance($this->_array);
	}
	
    /**
     * Iteratively reduce the vector to a single value using a callback
     * function.
     * 
     * If the optional $initial is available, it will be used at the beginning
     * of the process, or as a final result in case the vector is empty.
     * 
     * Callback format: `myFunc( $carry, $item ) : mixed`
     * 
     * Returns the resulting value.
     */
	public function reduce(callable $callback, $initial = null)
	{
		return array_reduce($this->_array, $callback, $initial);
	}
    
    
    /**
     * Normalise the vector to a range between 0 and 1.
     * 
     * This method expects the contents of the vector to be
     * numerical. You will need to filter any invalid values prior
     * to running the normalisation.
     */
    public function normalise(): Vector
    {
        return new Vector(math::normalise($this->_array));
    }
    
    /**
     * Alias of self::normalise().
     */
    public function normalize(): Vector
    {
        return self::normalise();
    }
	
    /**
     * Round all values in the vector up or down to the given decimal point precesion.
     */
    public function round(int $precision, int $mode = PHP_ROUND_HALF_UP): Vector
    {
        foreach ($this->_array as $key => $value)
        {
            if (is_numeric($value)) {
                $this->_array[$key] = round($value, $precision, $mode);
            }
        }
        return $this;
    }
}