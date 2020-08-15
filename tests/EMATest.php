<?php
declare(strict_types=1);
/**
*
* Datakit
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

use PHPUnit\Framework\TestCase;
use sqonk\phext\datakit\EMA;

class EMATest extends TestCase
{
    public function testRollingAverage()
    {
        $ema = new EMA(5);
        $expected = [1.0,1.33,1.89,2.59,3.4,4.26,5.18,6.12,7.08,8.05];
        foreach (range(1, 10) as $i) {
            $ema->add($i);
            $this->assertSame($ema->result(2), array_shift($expected));
        }
    }
    
    public function testInvalidMax()
    {
        $this->expectException(InvalidArgumentException::class);
        $ema = new EMA(0);
    }
    
    public function testAddInvalidValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $ema = new EMA(3);
        $ema->add('token');
    }
}