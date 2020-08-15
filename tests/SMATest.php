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
use sqonk\phext\datakit\SMA;

class SMATest extends TestCase
{
    public function testRollingAverage()
    {
        $sma = new SMA(5);
        $expected = [1.0,1.5,2.0,2.5,3.0,4.0,5.0,6.0,7.0,8.0];
        foreach (range(1, 10) as $i) {
            $sma->add($i);
            $this->assertSame($sma->result(2), array_shift($expected));
        }
    }
    
    public function testInvalidMax()
    {
        $this->expectException(InvalidArgumentException::class);
        $ema = new SMA(0);
    }
    
    public function testAddInvalidValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $ema = new SMA(3);
        $ema->add('token');
    }
}