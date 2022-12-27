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
 * A memory-efficient, variable-length array of variable-sized elements.
 * 
 * A PackedArray is sequentially indexed and non-associative.
 * 
 * Elements within the array may vary in their byte length. NULL values
 * are not accepted. Empty strings are internally stored as a 1-byte entry.
 * 
 * Auto-packing and unpacking is available for values going in and out of
 * the array.
 * 
 * Auto-Packing works as follows:
 * - integers are either encoded as 32bit/4 byte or 64bit/8-byte sequences,
 * depending on the hardware being used.
 * - decimal numbers are always encoded as double precision 8-byte sequences.
 * - strings are input directly.
 * - objects and arrays are serialised.
 * 
 * This class should not be considered a blanket replacement for native arrays,
 * instead the key is to identify when it is a better fit for any particular problem.
 * 
 * In general native arrays offer flexibility and speed over memory consumption, where
 * as a packed array prioritises memory usage for a little less flexibility. PackedArrays
 * are built to address situations where working with large data sets that challenge
 * the available RAM on the running machine can not be practically solved by other
 * means.
 * 
 * @implements \Iterator<int, mixed>
 * @implements \ArrayAccess<int, mixed>
 */
class PackedArray implements \ArrayAccess, \Countable, \Iterator
{
    protected \SplFileObject $buffer;
    protected int $size;
    protected PackedSequence $indexes;
    protected PackedSequence $lengths;
    protected PackedSequence $types;
        
    protected int $_iteratorIndex = 0;
    
	public function offsetSet(mixed $index, mixed $value): void
	{
		if ($index === null)
			$this->add($value);
		else
			$this->set($index, $value);
	}
	
	public function offsetGet(mixed $index): mixed {
		return $this->get($index);
	}
	
	public function offsetExists(mixed $index): bool {
		return $index < $this->count();
	}
	
	public function offsetUnset(mixed $index): void
	{
		$this->delete($index);
	}

    public function __tostring(): string {
        return sprintf("PackedArray(%d) %s...%s", $this->indexes->count(), $this->first(), $this->last());
    }
    
    public function rewind(): void {
        $this->_iteratorIndex = 0;
    }

    public function current(): mixed {
        return $this->get($this->_iteratorIndex);
    }

    public function key(): mixed {
        return $this->_iteratorIndex;
    }

    public function next(): void {
        ++$this->_iteratorIndex;
    }

    public function valid(): bool {
        return $this->_iteratorIndex < $this->count();
    }
    
    public function __clone()
    {
        $this->buffer = clone $this->buffer;
        $this->indexes = clone $this->indexes;
        $this->lengths = clone $this->lengths;
        $this->types = clone $this->types;
    }
    
    /**
     * Construct a new vector with the provided array.
     * 
     * @param list<mixed> $startingArray is an optional array of starting numbers to add
     * to the array.
     */
    public function __construct(array $startingArray = [])
    {
        $this->buffer = new \SplFileObject('php://memory', 'rw+');
        $this->indexes = new PackedSequence('I');
        $this->lengths = new PackedSequence('I');
        $this->types = new PackedSequence(1);
        
        $this->size = 0;
        
        foreach ($startingArray as $item)
            $this->add($item);
    }
    
    /**
     * Print all values to the output buffer. Optionally pass in a
     * title/starting message to print out first.
     */
    public function print(string $prependMessage = ''): void
    {
        if ($prependMessage)
            println($prependMessage);
        foreach ($this as $index => $value)
            println("[$index]", $value);
    }
    
    /**
     * Return the number of elements within the array.
     */
    public function count(): int {
        return $this->indexes->count();
    }
    
    /**
     * @internal
     * 
     * @return array{mixed, string}
     */
    protected function encode(mixed $value): array
    {
        if (is_int($value))
            return [pack('i', $value), 'i'];
        else if (is_float($value))
            return [pack('d', $value), 'r'];
        else if (is_array($value) || is_object($value))
            return [serialize($value), 'o'];
        else if (is_string($value) and $value === '')
            return [' ', 'e'];
        
        return [$value, 's'];
    }
    
    /**
     * @internal
     */
    protected function decode(mixed $value, string $type): mixed
    {
        if ($type == 'i')
            return unpack('i', $value)[1];
        else if ($type == 'r')
            return unpack('d', $value)[1];
        else if ($type == 'o')
            return unserialize($value);
        else if ($type == 'e')
            return '';
        
        return $value;
    }

    /**
     * Add a value to the end of the array. If the value is an array or a
     * traversable object then it will be serialised prior to being stored.
     */
    public function add(mixed ...$values): self
    {
        foreach ($values as $value)
        {
            if ($value === null)
                throw new \InvalidArgumentException('Null values are not accepted.');
        
            $this->buffer->fseek($this->size);
            
            // write out the index for the new value.
            $this->indexes->add($this->size);
        
            [$value, $type] = $this->encode($value);
        
            $len = strlen($value);
            $this->lengths->add($len);
            $this->types->add($type);
            $written = $this->buffer->fwrite($value); 
        
            $this->size += $len;
        }
        return $this;
    }
    
    /**
     * Insert a new item into the array at a given index anywhere up to the end of the array.
     */
    public function insert(int $index, mixed $newVal): self
    {
        $count = $this->count();
        
        if ($newVal === null)
            throw new \InvalidArgumentException('Null values are not accepted.');
        
        else if ($index < 0)
            throw new \InvalidArgumentException("Index [$index] out of bounds, count [$count].");
        
        else if ($index > $count)
            $index = $count;

        [$newVal, $type] = $this->encode($newVal);
        $newLen = strlen($newVal);
        
        // move everything after the insertion point along by the length of the new value.
        $this->buffer->fseek($this->size);
        $this->buffer->fwrite(str_repeat('0', $newLen));
        $this->size += $newLen;
        for ($i = $count-1; $i >= $index; $i--) 
        {
            [$val, $length, $pos] = $this->_get($i);
            $this->buffer->fseek($pos+$newLen);
            $this->lengths->set($i+1, $length); 
            $this->types->set($i+1, $this->types->get($i)); 
            $this->indexes->set($i+1, $pos+$newLen);
            $this->buffer->fwrite($val);
        }
        
        $this->buffer->fseek($this->indexes->get($index));
        $this->lengths->set($index, $newLen);
        $this->types->set($index, $type);
        $this->buffer->fwrite($newVal);
        
        return $this;
    }
    
    /**
     * Overwrite an existing value with the one provided. If $index is greater than the current
     * count then the value is appended to the end.
     */
    public function set(int $index, mixed $value): self
    {
        $count = $this->count();
        
        if ($index < 0)
            throw new \InvalidArgumentException('Index out of bounds.');
        
        if ($index < $count-1)
        {
            $this->delete($index);
            $this->insert($index, $value);
        }
        else if ($index == $count-1) {
            $this->delete($index);
            $this->add($value);
        }
        else {
            $this->add($value);
        }
        
        return $this;
    }
    
    /**
     * Return an item from the array at the given index.
     */
    public function get(int $index): mixed
    {
        [$value] = $this->_get($index);
        
        return $this->decode($value, $this->types->get($index));
    }
    
    /**
     * @internal
     * 
     * @return array{mixed, int, int}
     */
    protected function _get(int $index): array
    {
        $pos = $this->indexes->get($index);
        $len = $this->lengths->get($index);
        
        $this->buffer->fseek($pos);
        $value = $this->buffer->fread($len);
                
        return [$value, $len, $pos];
    }
    
    /**
     * Remove an item from the array  at the given index.
     */
    public function delete(int $index): self
    {
        $count = $this->count();
        
        if ($index > $count-1 or $index < 0)
            throw new \InvalidArgumentException('Index out of bounds.');
        
        $len = $this->lengths->get($index);
        
        if ($index < $count-1) 
        {
            // item is somewhere before the end..
            foreach (sequence($index+1, $count-1) as $next) {
                [$itemVal, $itemLen, $itemPos] = $this->_get($next);
                $this->buffer->fseek($itemPos - $len); // shift back by the length being removed. 
                $this->buffer->fwrite($itemVal);
                $this->indexes->set($next-1, $itemPos - $len);
            }
        }
        
        // remove last item.
        $this->size -= $len;
        $this->buffer->ftruncate($this->size);
        $this->indexes->pop();
        $this->lengths->delete($index);
        $this->types->delete($index);
        
        return $this;
    }
    
    /**
     * Pop an item off the end of the array. If $poppedValue is provided
     * then it is filled with the value that was removed.
     */
    public function pop(mixed &$poppedValue = null): PackedArray
    {
        if ($this->count() == 0) {
            trigger_error('Tried to pop an array that has no elements.', E_USER_WARNING);
            return $this;
        }
        
        $idx = $this->count()-1;
        $poppedValue = $this->get($idx);
        return $this->delete($idx);
    }
    
    /**
     * Shift an item off the start of the array. If $shiftedItem is provided
     * then it is filled with the value that was removed.
     */
    public function shift(&$shiftedItem = null): PackedArray
    {
        if ($this->count() == 0) {
            trigger_error('Tried to shift an array that has no elements.', E_USER_WARNING);
            return $this;
        }
        
        $shiftedItem = $this->get(0);
        return $this->delete(0);
    }
    
    /**
     * Remove all elements from the array.
     */
    public function clear(): self
    {
        $this->indexes->clear();
        $this->lengths->clear();
        $this->types->clear();
        
        $this->buffer->ftruncate(0);
        $this->buffer->rewind();
        
        $this->size = 0;
        
        return $this;
    }
    
	/**
	 * Return a new vector containing all indexes.
	 */
	public function keys(): Vector {
		return new Vector(range(0, $this->count()-1));
	}
    
	/**
	 * Returns TRUE if there are 0 elements in the array, FALSE otherwise.
	 */
	public function empty(): bool {
		return $this->count() == 0;
	}
    
	/**
	 * Return the first value in the array.
	 */
	public function first(): mixed {
		return $this->get(0);
	}
	
	/**
	 * Return the last value in the array.
	 */
	public function last(): mixed {
		return $this->get($this->count()-1);
	}
    
    /**
     * Returns TRUE if any of the values within the array are equal to the value
     * provided, FALSE otherwise.
     * 
     * A callback may be provided as the match to perform more complex testing.
     * 
     * Callback format: `myFunc($value) -> bool`
     * 
     * For basic (non-callback) matches, setting $strict to TRUE will enforce
     * type-safe comparisons.
     */
	public function any(mixed $match, bool $strict = false): bool
	{
		if (is_callable($match))
		{
			foreach ($this as $value) {
				if ($match($value))
					return true;
			}
		}
		
		else
        {
            foreach ($this as $value) {
                if ((! $strict and $value == $match) or ($strict and $value === $match))
                    return true;
            }
        }
        
        return false;
	}
	
    /**
     * Returns TRUE if all of the values within the array are equal to the value
     * provided, FALSE otherwise.
     * 
     * A callback may be provided as the match to perform more complex testing.
     * 
     * Callback format: `myFunc($value) -> bool`
     * 
     * For basic (non-callback) matches, setting $strict to TRUE will enforce
     * type-safe comparisons.
     */
	public function all(mixed $match, bool $strict = false): bool
	{
		$isCallback = is_callable($match);
		foreach ($this as $value) {
			if (($isCallback and ! $match($value)) or 
				(! $isCallback and (! $strict && $value != $match) or ($strict && $value !== $match)))
				return false;
		}
		return true;
	}
    
    /**
     * Search the array for the given needle (subject). This function is an
     * alias of any().
     */
    public function contains(mixed $needle): bool {
        return self::any($needle);
    }
    
    /**
     * Determines if the array ends with the needle.
     */
    public function ends_with(mixed $needle): bool {
        return $this->last() == $needle;
    }
    
    /**
     * Determines if the array starts with the needle.
     */
    public function starts_with(mixed $needle): bool {
        return $this->first() == $needle;
    }
    
    /**
     * Filter the contents of the array using the provided callback.
     * 
     * Callback format: `myFunc($value, $index) -> bool`
     */
	public function filter(callable $callback): PackedArray
	{
        $filtered = new PackedArray;
		foreach ($this as $index => $value)
            if ($callback($value, $index))
                $filtered[] = $value;
        return $filtered;
	}
    
    /**
     * Apply a callback function to the array.
     * 
     * Callback format: `myFunc($value, $index) -> mixed`
     */
    public function map(callable $callback): PackedArray
    {
        $mapped = new PackedArray;
        foreach ($this as $index => $value)
            $mapped[] = $callback($value, $index);
        return $mapped;        
    }
    
    /**
     * Pad the array to the specified length with a value. If $count is positive then
     * the array is padded on the right, if it's negative then on the left.
     */
	public function pad(int $count, mixed $value): self
	{
        if ($count > 0)
        {
            foreach (sequence($count-1) as $i)
                $this->add($value);
        }
        else
        {
            foreach (sequence(abs($count)-1) as $i)
                $this->insert(0, $value);
        }  
		
		return $this;
	}
    
    /**
     * Return a copy of the array only containing the number
     * of rows from the start as specified by $count.
     */
    public function head(int $count): PackedArray
    {
        if ($count == 0)
            return new PackedArray;
        
        if ($count >= $this->count()) 
            return $this->slice(0);
            
        return $this->slice(0, $count);
    }
    
    /**
     * Return a copy of the array only containing the number
     * of rows from the end as specified by $count.
     */
    public function tail(int $count): PackedArray
    {
        if ($count == 0)
            return new PackedArray;
        
        if ($count >= $this->count()) 
            return $this->slice(0);
            
        return $this->slice($this->count() - $count, $count);
    }
    
    /**
     * Return a copy of the array only containing the the rows
     * starting from $start through to the given length.
     */
    public function slice(int $start, ?int $length = null): PackedArray
    {
        if ($length === 0)
            return new PackedArray;
        
        $total = $this->count();
        if ($start >= $total)
            throw new \InvalidArgumentException('Start of slice is greater than the length of the array.');
		
        if (! $length || ($start + $length > $total-1)) 
            $length = $total - $start;
        
        $slice = new PackedArray;
        for ($i = $start; $i < $start+$length; $i++)
            $slice->add($this->get($i));
        
        return $slice;
    }
    
    /**
     * Return a copy of the array containing a random subset of the elements. The minimum and
     * maximum values can be supplied to focus the random sample to a more constrained subset.
     */
    public function sample(int $minimum, ?int $maximum = null): PackedArray
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
     * Continually apply a callback to a moving fixed window on the array. 
     * 
     * -- parameters:
     * @param int $window The size of the subset of the vector that is passed to the callback on each iteration. Note that this is the by default the maximum size the window can be. See `$minObservations`.
     * @param callable $callback The callback method that produces a result based on the provided subset of data.
     * @param int $minObservations The minimum number of elements that is permitted to be passed to the callback. If set to 0 the minimum observations will match whatever the window size is set to, thus enforcing the window size. If the value passed in is greater than the window size a warning will be triggered.
     * 
     * Callback format: `myFunc(Vector $rollingSet, mixed $index) : mixed`
     * 
     * @return PackedArray A PackedArray of the same item size as the receiver, containing the series of results produced by the callback method.
     */
    public function rolling(int $window, callable $callback, int $minObservations = 0): PackedArray 
    {
        if ($window < 1) {
            throw new \InvalidArgumentException("window must be a number greater than 0 ($window given)");
        }
        if ($minObservations > $window) {
            trigger_error("minObservations ($minObservations) is greater than given window ($window). It will be capped to the window size.", E_USER_WARNING);
        }

        $out = new PackedArray;
        $roller = new Vector;
        $roller->constrain($window);
        
        if ($minObservations < 1 || $minObservations > $window)
            $minObservations = $window;
        
        foreach ($this as $k => $v)
        {
            $roller->add($v);
            
            $r = null;
            if ($roller->count() >= $minObservations) {
                $r = $callback(clone $roller, $k);
            }
            if ($r !== null)
                $out->add($r);
        }
        
        return $out;
    }
    
    /**
     * Provide a maximum or minimum (or both) constraint for the values in the array.
     * 
     * If a value exceeds that constraint then it will be set to the constraint.
     * 
     * If either the lower or upper constraint is not needed then passing in null will
     * ignore it.
     * 
     * If $inPlace is TRUE then this operation modifies this array otherwise a copy is
     * returned.
     */
    public function clip(mixed $lower, mixed $upper = null): PackedArray
    {
        foreach ($this as $key => $value)
        {
            if ($lower !== null && is_numeric($value) && $value < $lower) 
                $this[$key] = $lower;
			
            else if ($upper !== null && is_numeric($value) && $value > $upper) 
                $this[$key] = $upper;
        }
        return $this;
    }
    
    /**
     * Swap the positions of 2 values within the array.
     */
    public function swap(int $index1, int $index2): PackedArray
    {
        $val1 = $this->get($index1);
        $this->set($index1, $this->get($index2));
        $this->set($index2, $val1);
        
        return $this;
    }
    
    /**
     * Sort the array in either `ASCENDING` or `DESCENDING` direction.
     * 
     * If $key is provided then the operation will be performed on
     * the corresponding sub value of array element, assuming each
     * element is an array or an object that provides array access.
     */
    public function sort(bool $dir = ASCENDING, ?string $key = null): PackedArray
    {
        $start = 0;
        $end = $this->count()-1;
        
        $cmp = function($newValue, $current) use ($dir) {
            if ($current === null)
                return ($dir == ASCENDING) ? -1 : 1; // packed arrays do not store NULL so this is the initialiser.
            if (is_string($newValue) and is_string($current)) {
                return strcmp($newValue, $current);
            }            
            
            return $newValue <=> $current;
        };
        
        while ($start < $end)
        {
            $minMax = null;
            $selectedIndex = 0;
            foreach (sequence($start, $end) as $index)
            {
                $value = $this->get($index);
                if ($key !== null)
                    $value = $value[$key];                
                
                if (($dir == ASCENDING && $cmp($value, $minMax) < 0) 
                    or ($dir == DESCENDING and $cmp($value, $minMax) > 0)) {
                    $selectedIndex = $index;
                    $minMax = $value;
                }
            }
            
            if ($selectedIndex != $start) {
                $this->swap($selectedIndex, $start);
            }
                
            $start++; 
        }
        
        return $this;
    }
    
    /**
     * Reserve the order of the elements.
     */
    public function reverse(): PackedArray
    {
        $count = $this->count();
        
        for ($i = 0; $i < ($count / 2); $i++)
        {
            if ($i != $count-1-$i)
                $this->swap($i, $count-1-$i);
        }
        
        return $this;
    }
    
    /**
     * Normalise the array to a range between 0 and 1.
     * 
     * Returns a PackedSequence.
     * 
     * This method expects the contents of the packed array to be
     * numerical. You will need to filter any invalid values prior
     * to running the normalisation.
     */
    public function normalise(): PackedSequence
    {
        $out = new PackedSequence('d');
        
        $length = $this->count(); 
        if ($length < 1) {
            trigger_error('The packed array has zero elements.', E_USER_NOTICE);
            return $out;
        }
        
        $min = $this->min();
        $max = $this->max();
        
        foreach ($this as $value)          
            $out[] = ($value - $min) / ($max - $min);
        
        return $out;
    }
    
    /**
     * Alias of self::normalise().
     */
    public function normalize(): PackedSequence {
        return self::normalise();
    }
    
    /**
     * Compute a sum of the values within the array.
     * 
     * If $key is provided then the operation will be performed on
     * the corresponding sub value of array element, assuming each
     * element is an array or an object that provides array access.
     */
    public function sum(mixed $key = null): int|float
	{
		$sum = 0;
        foreach ($this as $value)
            if (is_numeric($value))
                $sum += $value;
            else if ($key !== null and is_numeric($value[$key]))
                $sum += $value[$key];
        return $sum;
	}
	
    /**
     * Compute the average of the values within the array.
     * 
     * If $key is provided then the operation will be performed on
     * the corresponding sub value of array element, assuming each
     * element is an array or an object that provides array access.
     */
    public function avg(mixed $key = null): int|float
    {
        $count = $this->count();
        return ($count > 0) ? $this->sum($key) / $count : $count;
    }
	
    /**
     * Return the maximum value present within the array.
     * 
     * If $key is provided then the operation will be performed on
     * the corresponding sub value of array element, assuming each
     * element is an array or an object that provides array access.
     */
    public function max(mixed $key = null): int|float|null
    {
        $max = null;
        foreach ($this as $value)
        {
            if (is_numeric($value) and ($value > $max || $max === null))
                $max = $value;
            else if ($key !== null and is_numeric($value[$key]))
            {
                $val = $value[$key] ?? $max;
                if ($val > $max || $max === null)
                    $max = $val;
            }
        }
                
        return $max;
    }
    
    /**
     * Return the minimum value present within the array.
     * 
     * If $key is provided then the operation will be performed on
     * the corresponding sub value of array element, assuming each
     * element is an array or an object that provides array access.
     */
    public function min(mixed $key = null): int|float|null
    {
        $min = null;
        foreach ($this as $value)
        {
            if (is_numeric($value) and ($value < $min || $min === null))
                $min = $value;
            else if ($key !== null and is_numeric($value[$key]))
            {
                $val = $value[$key] ?? $min;
                if ($val < $min || $min === null)
                    $min = $val;
            }
        }
            
        return $min;
    }
    
    /**
     * Compute the product of the values within the array.
     * 
     * If $key is provided then the operation will be performed on
     * the corresponding sub value of array element, assuming each
     * element is an array or an object that provides array access.
     */
    public function product(mixed $key = null): int|float|null
	{
		$product = null;
        foreach ($this as $value)
        {
            if (is_numeric($value))
            {
                if ($product === null)
                    $product = $value;
                else
                    $product *= $value;
            }
            else if ($key !== null)
            {
                $val = $value[$key] ?? null;
                if (is_numeric($val))
                {
                    if ($product === null)
                        $product = $val;
                    else
                        $product *= $val;
                }
            }
        }
        if ($product === null)
            $product = 1;
            
        return $product;
	}

    /**
     * Compute the variance of values within the array.
     * 
     * If $key is provided then the operation will be performed on
     * the corresponding sub value of array element, assuming each
     * element is an array or an object that provides array access.
     */
	public function variance(mixed $key = null): int|float
	{
        if ($this->empty())
            return 0.0;
        $variance = 0.0;
        $average = $this->avg();

        foreach ($this as $i => $value)
        {
            // sum of squares of differences between 
            // all numbers and means.
            if (is_numeric($value)) {
                $variance += pow(($value - $average), 2);
            }
            else if ($key !== null and is_numeric($value[$key]))
            {
                $j = $value[$key] ?? null;
                if ($j !== null)
                    $variance += pow(($j - $average), 2);
            }
        }

        return $variance / $this->count();
	}
	
    /**
     * Round all values in the array up or down to the given decimal point precision.
     */
    public function round(int $precision, int $mode = PHP_ROUND_HALF_UP): self
    {
        foreach ($this as $key => $value)
        {
            if (is_numeric($value)) 
                $this[$key] = round($value, $precision, $mode);
        }
        return $this;
    }
}