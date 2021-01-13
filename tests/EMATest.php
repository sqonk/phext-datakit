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
    
    public function testCount()
    {
        $ema = new EMA(5);
        foreach (range(1, 10) as $i) {
            $ema->add($i);
        }
        
        $this->assertSame(10, count($ema));
    }
    
    public function testAcquiredAverages()
    {
        $ema = new EMA(5, 2);
        $expected = [1.00,1.33,1.89,2.59,3.4,4.26,5.18,6.12,7.08,8.05];
        foreach (range(1, 10) as $i) {
            $ema->add($i);
        }
        
        foreach ($ema as $i => $avg)
            $this->assertEquals($expected[$i], $avg);
    }
    
    public function testAddMultiWithReturnAllAndRounding()
    {
        $ema = new EMA(3, 2);
        $ema->add(1,0.43,3,4.33,5,6,7);

        $this->assertSame([1.0,0.72,1.86,3.09,4.05,5.02,6.01], $ema->all());
        $this->assertSame([1.0,0.715,1.858,3.094,4.047,5.023,6.012], $ema->all(3));
    }

    
    public function testExceptionForAttemptedRemoval()
    {
        $this->expectException(Exception::class);
        $ema = new EMA(3);
        $ema->add(1,2,3);
        unset($ema[1]);
    }
    
    public function testExceptionForAttemptedSet()
    {
        $this->expectException(Exception::class);
        $ema = new EMA(3);
        $ema->add(1,2,3);
        $ema[1] = 4;
    }
    
    public function testGetViaArrayAccess()
    {
        $ema = new EMA(3, 1);
        $ema->add(1,2,3);
        $this->assertSame(1.5, $ema[1]);
    }
    
    public function testAddViaArrayAccess()
    {
        $ema = new EMA(3, 2);
        $ema->add(1,2,3);
        $ema[] = 4;
        $this->assertSame(2.25, $ema[2]);
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