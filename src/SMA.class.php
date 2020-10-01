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
 */
class SMA
{
	protected $values;
	
    /**
     * Construct a new SMA with the specified maximum number of values.
     */
	public function __construct(int $maxItems)
	{
        if ($maxItems < 1)
            throw new \InvalidArgumentException("maxItems must be a number greater or equal to 1");
		$this->values = vector()->constrain($maxItems);
	}
	
    /**
     * Add a new value to the SMA. The value must be numerical in nature.
     */
	public function add($value)
	{
		if (! is_numeric($value))
			throw new \InvalidArgumentException("Only numeric values are accepted. [$value] was provided.");
		
		$this->values->add($value);
		
		return $this;
	}
	
    /**
     * Return the calculated result of the SMA as it currently stands. You can optionally pass in a value to
     * $precision to control the amount of decimal places that the result is rounded to.
     */
	public function result(?int $precision = null)
	{
		$avg = $this->values->avg();
		return ($precision !== null) ? round($avg, $precision) : $avg;
	}
	
	public function __toString()
	{
		return "SMA: ".$this->result();
	}
}