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
use sqonk\phext\datakit\math;

class MathLibTest extends TestCase
{
    public function testStandardDeviationWithSample()
    {
        $r = math::standard_deviation([1,2,3,4,5,6,7,8,9,10], true);
        $this->assertSame(round($r, 2), 3.03);
    }
    
    public function testStandardDeviationNoSample()
    {
        $r = math::standard_deviation([1,2,3,4,5,6,7,8,9,10], false);
        $this->assertSame(round($r, 2), 2.87);
    }
    
    public function testStandardDeviationNotEnoughValues()
    {
        $this->expectWarning();
        $this->expectWarningMessage('The array has zero elements');
        $r = math::standard_deviation([], false);
        $this->assertSame(false, $r);
        
        $this->expectWarningMessage('The array has only 1 element');
        $r = math::standard_deviation([1], false);
        $this->assertSame(false, $r);
    }
    
    public function testVariance()
    {
        $input = [1,2,3,4];
        $this->assertSame(1.25, round(math::variance($input), 2));
        
        $this->assertSame(0.0, math::variance([]));
    }
    
    public function testAvg()
    {
        $r = math::avg([1,2,3,4,5,6,7,8,9,10]);
        $this->assertSame(5.5, $r);
        
        $r = math::avg([]);
        $this->assertSame($r, 0);
    }
    
    public function testMin()
    {
        $this->assertSame(null, math::min([]));
        
        $r = math::min([-1, -0.5, -0.7, 0.1, -2.3, -1.3]);
        $this->assertSame(-2.3, round($r, 1));
    }
    
    public function testMax()
    {
        $this->assertSame(null, math::max([]));
        
        $r = math::max([-1, -0.5, -0.7, -2.3, -1.3]);
        $this->assertSame(-0.5, round($r, 1));
    }
    
    public function testMedian()
    {
        $r = math::median([1,2,3,4,5,6,7]);
        $this->assertSame(4, $r);
        
        $r = math::median([1,2,3,4,5,6]);
        $this->assertSame(3.5, $r);
        
        $this->assertSame(6.3, math::median([5.1,7.0,6.3]));
        
        $this->expectWarning();
        $this->expectWarningMessage('The array has zero elements');
        $this->assertSame(false, math::median([]));
    }
    
    public function testQuantile()
    {
        $arr = [1,2,3,4,5,6,7,8,9,10];
        
        $this->assertSame(1.0, math::quantile($arr, 0));
        $this->assertSame(3.25, math::quantile($arr, 0.25));
        $this->assertSame(5.5, math::quantile($arr, 0.5));
        $this->assertSame(7.75, math::quantile($arr, 0.75));
        $this->assertSame(10, math::quantile($arr, 1));
        
        $this->expectWarning();
        $this->expectWarningMessage('The array has zero elements');
        $this->assertSame(false, math::quantile([], 0.25));
    }
    
    public function testCumulativeMin()
    {
        $input = [10,9,8,7,6,5,4,3,2,3,1];
        $expected = [10,9,8,7,6,5,4,3,2,2,1];
        $r = math::cumulative_min($input);
        
        while (count($r))
            $this->assertSame(array_shift($r), array_shift($expected));
    }
    
    public function testCumulativeMax()
    {
        $input = [1,2,3,2,4,3,5];
        $expected = [1,2,3,3,4,4,5];
        $r = math::cumulative_max($input);
        
        while (count($r))
            $this->assertSame(array_shift($r), array_shift($expected));
    }
    
    public function testCumulativeSum()
    {
        $expected = [1,3,6,10,15];
        $results = math::cumulative_sum([1,2,3,4,5]);
        
        foreach ($results as $r)
            $this->assertSame($r, array_shift($expected));
    }
    
    public function testCumulativeProd()
    {
        $expected = [1,2,6,24];
        $results = math::cumulative_prod([1,2,3,4]);
        
        foreach ($results as $r)
            $this->assertSame($r, array_shift($expected));
    }
    
    public function testDistances()
    {
        $expected = [1,4,9,16];
        $results = math::distances([1,2,3,4], [2,4,6,8]);
        
        foreach ($results as $r)
            $this->assertSame($r, array_shift($expected));
    }
    
    public function testPearsonCorrelation()
    {
        $this->assertSame(1.0, math::correlation_pearson([1,2,3,4], [1,2,3,4]));
        $this->assertSame(-1.0, math::correlation_pearson([1,2,3,4], [4,3,2,1]));
        $this->assertSame(0.15, round(math::correlation_pearson([1,2,3,4], [6,2,8,5]), 2));
        $this->assertSame(1.0, math::correlation_pearson([], []));
        $this->assertSame(-1.0, math::correlation_pearson([1,2,3,4], [6,2,8]));
    }
    
    public function testSpearmanCorrelation()
    {
        $this->assertSame(1, math::correlation_spearman([1,2,3,4], [1,2,3,4]));
        $this->assertSame(-1, math::correlation_spearman([1,2,3,4], [4,3,2,1]));
        $this->assertSame(0, math::correlation_spearman([1,2,3,4], [6,2,8,5]));
        $this->assertSame(1, math::correlation_spearman([], []));
        $this->assertSame(null, math::correlation_spearman([1,2,3,4], [6,2,8]));
    }
}