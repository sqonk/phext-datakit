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
 * A simple class for management of a Simple Moving Average. It works by alternating between
 * adding new values to the array and calculating the current average.
 * 
 * SMA adheres to various array-like behaviour protocols. You should keep in 
 * mind that whenever you access values from the class you will be receiving the relevant
 * average, not the original raw value you placed in previously.
 * 
 * @implements \IteratorAggregate<int, int|float>
 * @implements \ArrayAccess<int, int|float>
 */
class SMA implements \ArrayAccess, \Countable, \IteratorAggregate
{
	protected Vector $values;
    protected Vector $averages;
    protected ?int $defaultPrecision = null;
    
    // =====================
    // = Interface methods =
    // =====================
    
    /**
     * Returns the total number of averages calculated so far.
     */
    public function count(): int {
        return $this->averages->count();
    }
    
	public function getIterator(): \ArrayIterator
	{
        $array = $this->defaultPrecision ? $this->averages->round($this->defaultPrecision)->array() : 
            $this->averages->array();
		return new \ArrayIterator($array);
	}
    
	public function offsetSet(mixed $index, mixed $value): void
	{
		if ($index === null)
			$this->add($value);
		else
			throw new \Exception("Existing values of an SMA can not be overwritten.");
	}
	
	public function offsetGet(mixed $index): mixed
	{
		$value = $this->averages->get($index);
        if (is_int($this->defaultPrecision))
            $value = round($value, $this->defaultPrecision);
        
        return $value;
	}
	
	public function offsetExists(mixed $index): bool {
		return array_key_exists($index, $this->averages->array());
	}
	
	public function offsetUnset(mixed $index): void {
		throw new \Exception("Existing values can not be removed from an SMA.");
	}
    
    
    // ======================
    // = Main class methods =
    // ======================
	
    /**
     * Construct a new SMA with the specified maximum number of values. 
     * 
     * -- parameters:
     * @param int $maxItems The maximum amount of values that the moving average is allowed to work off of. As new values are added onto the end, old values are moved off the front.
     * @param ?int $defaultPrecision If set, will automatically round all averages to given decimal precision.
     */
	public function __construct(int $maxItems, ?int $defaultPrecision = null)
	{
        if ($maxItems < 1)
            throw new \InvalidArgumentException("maxItems must be a number greater or equal to 1");
        
		$this->values = vector()->constrain($maxItems);
        $this->averages = vector();
        
        $this->defaultPrecision = $defaultPrecision;
	}
	
    /**
     * Add one or more new values to the SMA. The value must be numerical in nature.
     */
	public function add(mixed ...$values): SMA
	{
        foreach ($values as $value)
        {
    		if (! is_numeric($value))
    			throw new \InvalidArgumentException("Only numeric values are accepted. [$value] was provided.");
		
    		$this->values->add($value);
            $this->averages->add($this->values->avg());
        }
		
		return $this;
	}
	
    /**
     * Return the calculated result of the SMA as it currently stands, optionally rounding it to the specified
     * precision. If $precision is NULL then it falls back to the default precision specified at the time 
     * of object creation.
     * 
     * -- parameters:
     * @param ?int $precision The amount of decimal points to round to. If NULL then the default precision of the EMA object is used.
     * 
     * @return float The most recent calculated moving average.
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
     * 
     * -- parameters:
     * @param ?int $precision The amount of decimal points to round to. If NULL then the default precision of the SMA object is used.
     * 
     * @return list<int|float> The list of all acquired averages.
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
	
	public function __tostring(): string {
		return "SMA: ".$this->result();
	}
}