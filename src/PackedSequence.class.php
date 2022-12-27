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
 * A memory-efficient, variable-length array of fixed size elements.
 * 
 * A PackedSequence is sequentially indexed and non-associative.
 * 
 * All elements within the array must be the same amount of bytes. NULL values
 * are not accepted.
 * 
 * Auto-packing and unpacking is available for values going in and out of
 * the array.
 * 
 * It is particularly useful for large numerical arrays or indexes.
 * 
 * @implements \Iterator<int, int|float>
 * @implements \ArrayAccess<int, int|float>
 */
class PackedSequence implements \ArrayAccess, \Countable, \Iterator
{
    protected \SplFileObject $buffer;
    protected int $size = 0;
    
    protected string|int $itemSize;
    protected ?string $packCode = null;
    
    protected int $_iteratorIndex = 0;
            
    // -------- Class Interfaces
    
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
	
	public function offsetUnset(mixed $index): void {
		$this->delete($index);
	}

    public function __tostring(): string
    {
        $count = $this->count();
        return sprintf("PackedSequence(%d) %s...%s", $count, $this->get(0), $this->get($count-1));
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
        $prev = $this->buffer;
        $prev->rewind();
        $this->buffer = new \SplFileObject('php://memory', 'rw+');
        $this->buffer->fwrite($prev->fread($this->size));
    }
    
    // ----- Class methods
    
    /**
     * $itemSize should be either a string code accepted by PHP's built-in
     * pack() method, or an integer specifying the raw byte size if no
     * packing is required.
     * 
     * -- parameters:
     * @param ?list<int|float> $startingValues is an optional array of starting numbers to add
     * to the array.
     */
    public function __construct(int|string $itemSize, ?array $startingValues = null)
    {
        if (is_int($itemSize))
            $this->itemSize = $itemSize;
        else if (is_string($itemSize)) {
            $this->packCode = $itemSize;
            $this->itemSize = strlen(pack($itemSize, 1));
        }
        
        $this->buffer = new \SplFileObject('php://memory', 'rw+');
        
        if ($startingValues)
            foreach ($startingValues as $value)
                $this->add($value);
    }
    
    /**
     * Return the amount of items within the Packed Sequence.
     */
    public function count(): int {
        return $this->size / $this->itemSize;
    }
    
    /**
     * Print all values to the output buffer.
     */
    public function print(string $prependMessage = ''): void
    {
        if ($prependMessage)
            println($prependMessage);
        foreach ($this as $index => $value)
            println("[$index]", $value);
    }
    
    /**
     * @internal
     * 
     * Central point for auto packing. Writes to the current position.
     */
    protected function write(int|float|string $value): void
    {
        if ($this->packCode !== null)
            $value = pack($this->packCode, $value);
        $this->buffer->fwrite($value);
    }
    
    /**
     * Add a value to the end of the array. If the value is an array or a
     * traversable object then each element of it will instead be added.
     */
    public function add(mixed ...$values): self
    {
        foreach ($values as $value)
        {
            if (is_array($value) or (is_object($value) and $value instanceof \Traversable)) {
                foreach ($value as $item)
                    $this->add($item);
            }
            else if (! var_is_stringable($value))
                throw new \InvalidArgumentException('All values added to a PackedSequence must be capable of being converted to a string.');
        
            else {
                $this->buffer->fseek($this->size);
                $this->write($value);
                $this->size += $this->itemSize;
            }
        }
        
        
        return $this;
    }
    
    /**
     * Insert a new item into the array at a given index anywhere up to the end of the array.
     */
    public function insert(int $index, int|float|string $value): self
    {
        $count = $this->count();
        
        if (! var_is_stringable($value))
            throw new \InvalidArgumentException('All values added to a PackedSequence must be capable of being converted to a string.');
        
        else if ($index > $count-1 or $index < 0)
            throw new \InvalidArgumentException('Index out of bounds.');
        
        if ($index < $count)
        {
            // move everything after the insertion point along by the length of the new value.
            $this->size += $this->itemSize;
            for ($i = $count-1; $i >= $index; $i--) 
            {
                $val = $this->get($i);
                $this->buffer->fseek(($i+1) * $this->itemSize);
                $this->write($val);
            }
            
            $this->buffer->fseek($index * $this->itemSize);
            $this->write($value); 
        }

        return $this;
    }
    
    /**
     * Overwrite an existing value with the one provided. If $index is greater than the current
     * count then the value is appended to the end.
     */
    public function set(int $index, int|float|string $value): self
    {
        $count = $this->count();
        
        if (! var_is_stringable($value))
            throw new \InvalidArgumentException('All values added to a PackedSequence must be capable of being converted to a string.');
        
        else if ($index < 0)
            throw new \InvalidArgumentException("Index [$index] out of bounds.");
        
        if ($index <= $count-1)
        {
            $this->buffer->fseek($index * $this->itemSize);
            $this->write($value);
        }
        else {
            $this->add($value);
        }
            
        
        return $this;
    }
    
    /**
     * Return an item from the array at the given index.
     */
    public function get(int $index): int|float|string
    {
        $count = $this->count();
        if ($index > $count-1 or $index < 0)
            throw new \InvalidArgumentException("Index [$index] out of bounds. Range is [0..$count]");
        
        $this->buffer->fseek($index * $this->itemSize);
        
        $value = $this->buffer->fread($this->itemSize);
        if ($this->packCode !== null)
            $value = unpack($this->packCode, $value)[1];
        return $value;
    }
    
    /**
     * Remove an item from the array  at the given index.
     */
    public function delete(int $index): self
    {
        $count = $this->count();
        
        if ($index > $count-1 or $index < 0)
            throw new \InvalidArgumentException('Index out of bounds.');
        
        if ($index < $count-1) 
        {
            // item is somewhere before the end..
            foreach (sequence($index+1, $count-1) as $next) {
                $val = $this->get($next);
                $this->buffer->fseek(($next-1) * $this->itemSize); 
                $this->write($val);
            }
        }
        
        // remove last item.
        $this->size -= $this->itemSize;
        $this->buffer->ftruncate($this->size);
        
        return $this;
    }
    
    /**
     * Pop an item off the end of the array. If $poppedValue is provided
     * then it is filled with the value that was removed.
     */
    public function pop(int|float|string &$poppedValue = null): self
    {
        if ($this->count() == 0) {
            trigger_error('Tried to pop a sequence that has no elements.', E_USER_WARNING);
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
    public function shift(int|float|string &$shiftedItem = null): self
    {
        if ($this->count() == 0) {
            trigger_error('Tried to shift a sequence that has no elements.', E_USER_WARNING);
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
		$this->size = 0;
        $this->buffer->ftruncate(0);
        $this->buffer->rewind();
        
        return $this;
	}
    
	/**
	 * Return a new vector containing all indexes.
	 */
	public function keys(): Vector
	{
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
	public function first(): int|float {
		return $this->get(0);
	}
	
	/**
	 * Return the last value in the array.
	 */
	public function last(): int|float {
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
    public function contains(int|float $needle): bool {
        return self::any($needle);
    }
    
    /**
     * Determines if the array ends with the needle.
     */
    public function ends_with(int|float $needle): bool {
        return $this->last() == $needle;
    }
    
    /**
     * Determines if the array starts with the needle.
     */
    public function starts_with(int|float $needle): bool {
        return $this->first() == $needle;
    }
    
    /**
     * Filter the contents of the array using the provided callback.
     * 
     * Callback format: `myFunc($value, $index): bool`
     */
	public function filter(callable $callback): self
	{
        $size = $this->packCode ?? $this->itemSize;
        $filtered = new PackedSequence($size);
		foreach ($this as $index => $value)
            if ($callback($value, $index))
                $filtered[] = $value;
        return $filtered;
	}
    
    /**
     * Apply a callback function to the array.
     * 
     * Callback format: `myFunc($value, $index): mixed`
     */
    public function map(callable $callback): PackedSequence
    {
        $size = $this->packCode ?? $this->itemSize;
        $mapped = new PackedSequence($size);
        foreach ($this as $index => $value)
            $mapped[] = $callback($value, $index);
        return $mapped;        
    }
    
    /**
     * Pad the array to the specified length with a value. If $count is positive then
     * the array is padded on the right, if it's negative then on the left.
     */
	public function pad(int $count, int|float $value): self
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
    public function head(int $count): PackedSequence
    {
        if ($count >= $this->count()) 
            return $this->slice(0);
            
        return $this->slice(0, $count);
    }
    
    /**
     * Return a copy of the array only containing the number
     * of rows from the end as specified by $count.
     */
    public function tail(int $count): PackedSequence
    {
        if ($count == 0) {
            $size = $this->packCode ?? $this->itemSize;
            return new PackedSequence($size);
        }
            
        if ($count >= $this->count()) 
            return $this->slice(0);
            
        return $this->slice($this->count() - $count, $count);
    }
    
    /**
     * Return a copy of the array only containing the the rows
     * starting from $start through to the given length.
     */
    public function slice(int $start, ?int $length = null): PackedSequence
    {
        $total = $this->count();
        if ($start >= $total)
            throw new \InvalidArgumentException('Start of slice is greater than the length of the array.');
		
        if ($length === null || ($length && $start + $length > $total-1)) 
            $length = $total - $start;
        
        $size = $this->packCode ?? $this->itemSize;
        $slice = new PackedSequence($size);
        for ($i = $start; $i < $start+$length; $i++)
            $slice->add($this->get($i));
        
        return $slice;
    }
    
    /**
     * Return a copy of the array containing a random subset of the elements. The minimum and
     * maximum values can be supplied to focus the random sample to a more constrained subset.
     */
    public function sample(int $minimum, ?int $maximum = null): PackedSequence
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
     * Continually apply a callback to a moving fixed window on the sequence. 
     * 
     * -- parameters:
     * @param int $window The size of the subset of the vector that is passed to the callback on each iteration. Note that this is the by default the maximum size the window can be. See `$minObservations`.
     * @param callable $callback The callback method that produces a result based on the provided subset of data.
     * @param int $minObservations The minimum number of elements that is permitted to be passed to the callback. If set to 0 the minimum observations will match whatever the window size is set to, thus enforcing the window size. If the value passed in is greater than the window size a warning will be triggered.
     * 
     * Callback format: `myFunc(Vector $rollingSet, mixed $index) : mixed`
     * 
     * @return PackedSequence A PackedSequence of the same item size as the receiver, containing the series of results produced by the callback method.
     */
    public function rolling(int $window, callable $callback, int $minObservations = 0): PackedSequence 
    {
        if ($window < 1) {
            throw new \InvalidArgumentException("window must be a number greater than 0 ($window given)");
        }
        if ($minObservations > $window) {
            trigger_error("minObservations ($minObservations) is greater than given window ($window). It will be capped to the window size.", E_USER_WARNING);
        }

        $out = new PackedSequence($this->itemSize);
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
     */
    public function clip($lower, $upper = null): PackedSequence
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
    public function swap(int $index1, int $index2): PackedSequence
    {
        $val1 = $this->get($index1);
        $this->set($index1, $this->get($index2));
        $this->set($index2, $val1);
        return $this;
    }
    
    /**
     * Sort the array in either `ASCENDING` or `DESCENDING` direction.
     */
    public function sort(bool $dir = ASCENDING): PackedSequence
    {
        $start = 0;
        $end = $this->count()-1;
        
        while ($start < $end)
        {
            $minMax = ($dir == ASCENDING) ? PHP_INT_MAX : -PHP_INT_MAX;
            $selectedIndex = 0;
            foreach (sequence($start, $end) as $index)
            {
                $value = $this->get($index);
                if (($dir == ASCENDING and $value < $minMax) or ($dir == DESCENDING and $value > $minMax)) {
                    $selectedIndex = $index;
                    $minMax = $value;
                }
            }
        
            $this->swap($selectedIndex, $start);
            $start++;
        }
        
        return $this;
    }
    
    /**
     * Reserve the order of the elements.
     */
    public function reverse(): PackedSequence
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
     * Compute a sum of the values within the array.
     */
    public function sum(): int|float
	{
		$sum = 0;
        foreach ($this as $value)
            $sum += $value;
        return $sum;
	}
	
    /**
     * Compute the average of the values within the array.
     */
    public function avg(): int|float
    {
        $count = $this->count();
        return ($count > 0) ? $this->sum() / $count : $count;
    }
	
    /**
     * Return the maximum value present within the array.
     */
    public function max(): int|float|string|null
    {
        $max = null;
        foreach ($this as $value)
            if ($value > $max || $max === null)
                $max = $value;
        return $max;
    }
    
    /**
     * Return the minimum value present within the array.
     */
    public function min(): int|float|string|null
    {
        $min = null;
        foreach ($this as $value)
            if ($value < $min || $min === null)
                $min = $value;
        return $min;
    }
    
    /**
     * Normalise the array to a range between 0 and 1.
     */
    public function normalise(): PackedSequence
    {
        $out = new PackedSequence('d');
        
        $length = $this->count(); 
        if ($length < 1) {
            trigger_error('The packed sequence has zero elements.', E_USER_NOTICE);
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
     * Compute the product of the values within the array.
     */
    public function product(): int|float
	{
		$product = null;
        foreach ($this as $value)
            if ($product === null)
                $product = $value;
            else
                $product *= $value;
        
        if ($product === null)
            $product = 1;
            
        return $product;
	}

    /**
     * Compute the variance of values within the array. If the array is empty
     * FALSE will be returned.
     */
	public function variance(): int|float
	{
        if ($this->empty())
            return 0.0;
        $variance = 0.0;
        $average = $this->avg();

        foreach ($this as $i)
        {
            // sum of squares of differences between 
            // all numbers and means.
            if (is_numeric($i))
                $variance += pow(($i - $average), 2);
        }

        return $variance / $this->count();
	}
	
    /**
     * Round all values in the array up or down to the given decimal point precision.
     * 
     * -- parameters:
     * @param int $precision The number of decimal digits to round to.
     * @param int-mask<1,2,3,4> $mode The rounding mode used.
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