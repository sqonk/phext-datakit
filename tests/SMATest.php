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
    
    public function testCount()
    {
        $sma = new SMA(5);
        $expected = [1.0,1.5,2.0,2.5,3.0,4.0,5.0,6.0,7.0,8.0];
        foreach (range(1, 10) as $i) {
            $sma->add($i);
        }
        
        $this->assertSame(10, count($sma));
    }
    
    public function testAcquiredAverages()
    {
        $sma = new SMA(5);
        $expected = [1.0,1.5,2.0,2.5,3.0,4.0,5.0,6.0,7.0,8.0];
        foreach (range(1, 10) as $i) {
            $sma->add($i);
        }
        
        foreach ($sma as $i => $avg)
            $this->assertEquals($expected[$i], $avg);
    }
    
    public function testAddMultiWithReturnAllAndRounding()
    {
        $sma = new SMA(3, 2);
        $sma->add(1,0.43,3,4.33,5,6,7);

        $this->assertSame([1.0,0.72,1.48,2.59,4.11,5.11,6.0], $sma->all());
        $this->assertSame([1.0,0.715,1.477,2.587,4.11,5.11,6.0], $sma->all(3));
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
    
    public function testExceptionForAttemptedRemoval()
    {
        $this->expectException(Exception::class);
        $sma = new SMA(3);
        $sma->add(1,2,3);
        unset($sma[1]);
    }
    
    public function testExceptionForAttemptedSet()
    {
        $this->expectException(Exception::class);
        $sma = new SMA(3);
        $sma->add(1,2,3);
        $sma[1] = 4;
    }
    
    public function testGetViaArrayAccess()
    {
        $sma = new SMA(3);
        $sma->add(1,2,3);
        $this->assertSame(1.5, $sma[1]);
    }
    
    public function testAddViaArrayAccess()
    {
        $sma = new SMA(3);
        $sma->add(1,2,3);
        $sma[] = 4;
        $this->assertSame(2, $sma[2]);
    }
}