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
class EMA
{
	protected $limit;
	protected $values;
	protected $previous = 0;
	protected $latest = 0;
	
    /**
     * Construct a new EMA with the specified maximum number of values.
     */
	public function __construct(int $maxItems)
	{
        if ($maxItems < 1)
            throw new \InvalidArgumentException("maxItems must be a number greater or equal to 1");
		$this->values = vector()->constrain($maxItems);
		$this->limit = $maxItems;
	}
	
    /**
     * Add a new value to the EMA. The value must be numerical in nature.
     */
	public function add($value)
	{
		if (! is_numeric($value))
			throw new \InvalidArgumentException("Only numeric values are accepted. [$value] was provided.");
		
		$this->values->add($value);
		
		$this->calc($value); // update the moving average based on the new set of values.
		
		return $this;
	}
	
	protected function calc($newValue)
	{
		$count = count($this->values);
		if ($this->values->count() == 1)
			$this->latest = $this->values->first();
		
		else
		{
			$this->previous = $this->latest;
			
			$k = 2 / ($this->limit + 1);
			$this->latest = ($k * $newValue) + ((1 - $k) * $this->previous);
		}
	}
	
    /**
     * Return the calculated result of the EMA as it currently stands. You can optionally pass in a value to
     * $precision to control the amount of decimal places that the result is rounded to.
     */
	public function result(?int $precision = null)
	{
		return ($precision !== null) ? round($this->latest, $precision) : $this->latest;
	}
	
	public function __toString()
	{
		return "EMA: {$this->latest}";
	}
}