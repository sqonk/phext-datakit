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
 * A simple class for management of a Exponential Moving Average. It works by alternating between
 * adding new values to the array and calculating the current average.
 */
class EMA implements \ArrayAccess, \Countable, \IteratorAggregate
{
	protected int $limit;
	protected Vector $values;
    protected Vector $averages;
	protected int|float $previous = 0;
    protected ?int $defaultPrecision = null;
    
    // =====================
    // = Interface methods =
    // =====================
    
    /**
     * Returns the total number of averages calculated so far.
     */
    public function count(): int
    {
        return $this->averages->count();
    }
    
	public function getIterator(): \ArrayIterator
	{
        $array = $this->defaultPrecision ? $this->averages->round($this->defaultPrecision)->array() : 
            $this->averages->array();
		return new \ArrayIterator($array);
	}
    
	public function offsetSet($index, $value): void
	{
		if ($index === null)
			$this->add($value);
		else
			throw new \Exception("Existing values of an SMA can not be overwritten.");
	}
	
	public function offsetGet($index): mixed
	{
		$value = $this->averages->get($index);
        if (is_int($this->defaultPrecision))
            $value = round($value, $this->defaultPrecision);
        
        return $value;
	}
	
	public function offsetExists($index): bool
	{
		return array_key_exists($index, $this->averages->array());
	}
	
	public function offsetUnset($index): void
	{
		throw new \Exception("Existing values can not be removed from an SMA.");
	}
	
    /**
     * Construct a new EMA with the specified maximum number of values.
     * 
     * -- parameters:
     * @param $maxItems The maximum amount of values that the moving average is allowed to work off of. As new values are added onto the end, old values are moved off the front.
     * @param $defaultPrecision If set, will automatically round all averages to the given decimal precision.
     */
	public function __construct(int $maxItems, ?int $defaultPrecision = null)
	{
        if ($maxItems < 1)
            throw new \InvalidArgumentException("maxItems must be a number greater or equal to 1");
        
		$this->values = vector()->constrain($maxItems);
		$this->limit = $maxItems;
        
        $this->averages = vector();
        
        $this->defaultPrecision = $defaultPrecision;
	}
	
    /**
     * Add one or more new values to the EMA. The value must be numerical in nature.
     */
	public function add(...$values): EMA
	{
        foreach ($values as $value)
        {
    		if (! is_numeric($value))
    			throw new \InvalidArgumentException("Only numeric values are accepted. [$value] was provided.");
		
    		$this->values->add($value);		
    		$this->averages->add($this->calc($value)); 
        }
		
		return $this;
	}
	
	protected function calc($newValue): float
	{
		$count = count($this->values);
		if ($this->values->count() == 1)
			return $this->values->first();

		$this->previous = $this->averages->last();
		
		$k = 2 / ($this->limit + 1);
		return ($k * $newValue) + ((1 - $k) * $this->previous);
	}
	
    /**
     * Return the calculated result of the EMA as it currently stands. You can optionally pass in a value to
     * $precision to control the amount of decimal places that the result is rounded to. If $precision is NULL then it falls back to the default precision specified at the time of object creation.
     */
	public function result(?int $precision = null): float
	{
        if ($precision === null)
            $precision = $this->defaultPrecision;
        
		$avg = $this->averages->last();
		return ($precision !== null) ? round($avg, $precision) : $avg;
	}
    
    /**
     * Return all acquired averages, optionally rounding them to the specified precision. If $precision is
     * NULL then it falls back to the default precision specified at the time of object creation.
     */
    public function all(?int $precision = null): array
    {
        if ($precision === null)
            $precision = $this->defaultPrecision;
        
        if ($precision) {
            $copy = clone $this->averages;
            return $copy->round($precision)->array();
        }
        
        return $this->averages->array();
    }
	
	public function __tostring(): string
	{
		return "EMA: {$this->latest}";
	}
}