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
use sqonk\phext\datakit\PackedArray;

class PackedArrayTest extends TestCase
{
    public function testPrefill()
    {
        $input = [1,2,3,4,5,6,7,8,9];
        $ps = new PackedArray($input);
        foreach ($ps as $value)
            $this->assertSame($value, array_shift($input));
    }
    
    public function testPrefillFloat()
    {
        $input = [1.5,2.2,3.4,4.8,5.4,6.1,7.9,8.8,9.0];
        $ps = new PackedArray($input);
        foreach ($ps as $value)
            $this->assertSame($value, array_shift($input));
    }
    
    public function testGet()
    {
        $input = [1,2,3,4,5,6,7,8,9];
        $ps = new PackedArray($input);
        
        $this->assertSame(1, $ps->get(0));
        $this->assertSame(9, $ps->get(8));
        $this->assertSame(4, $ps->get(3));
        
        $this->expectException(InvalidArgumentException::class);
        $ps->get(-1);
        $ps->get(9);
    }
    
    public function testAdd()
    {
        $ps = new PackedArray();
        $ps->add(1);
        $this->assertSame(1, $ps->get(0));
        
        $ps->add(2,3);
        $this->assertSame(2, $ps->get(1));
        $this->assertSame(3, $ps->get(2));
    }
    
    public function testInsert()
    {
        $ps = new PackedArray([1,'a string',4,'day', [1,2,3], 'bc']);
        $ps->insert(2, 'bill');
        
        $expected = [1,'a string', 'bill', 4,'day', [1,2,3], 'bc'];
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
        
        $this->expectException(InvalidArgumentException::class);
        $ps->insert(-1, 9);
        $ps->insert(5, 9);
        
        $this->expectExceptionMessage('All values added to a PackedSequence must be capable of being converted to a string.');
        $ps->insert(2, null);
    }
    
    public function testSet()
    {
        $ps = new PackedArray([1,2,3]);
        $ps->set(1, 'word');
        $this->assertSame('word', $ps->get(1));
        
        $ps->set(5, 10); // test adding via index outside of current range.
        $this->assertSame(10, $ps->get(3));
        
        $this->expectException(InvalidArgumentException::class);
        $ps->set(-1, 7);
    }
    
    public function testDelete()
    {
        $input = [1,2,3,4,5,6,7,8,9];
        $ps = new PackedArray($input);
        $ps->delete(1);
        
        $expected = [1,3,4,5,6,7,8,9];
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
        
        $expected = [3,4,5,6,7,8];
        $ps->delete(0);
        $ps->delete($ps->count()-1);
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
    }
    
    public function testPop()
    {
        $ps = new PackedArray([1,2,3]);
        $ps->pop($item);
        
        $this->assertSame(3, $item);
        $expected = [1,2];
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tried to pop an array that has no elements.');
        $ps = new PackedArray;
        $ps->pop();
    }
    
    public function testShift()
    {
        $ps = new PackedArray([1,2,3]);
        $ps->shift($item);
        
        $this->assertSame(1, $item);
        $expected = [2,3];
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tried to shift an array that has no elements.');
        $ps = new PackedArray;
        $ps->shift();
    }
    
    public function testHead()
    {
        $ps = new PackedArray([1,2,3,4,5,6,7,8,9]);
        $head = $ps->head(3);
        
        $expected = [1,2,3];
        foreach ($head as $i => $v)
            $this->assertSame($v, $expected[$i]);
        
        $head = $ps->head(0);
        $this->assertSame(count($head), 0);
    }
    
    public function testTail()
    {
        $ps = new PackedArray([1,2,3,4,5,6,7,8,9]);
        $tail = $ps->tail(3);
        
        $expected = [7,8,9];
        foreach ($tail as $i => $v)
            $this->assertSame($v, $expected[$i]);
        
        $tail = $ps->tail(0);
        $this->assertSame(count($tail), 0);
    }
    
    public function testSlice()
    {
        $ps = new PackedArray([1,2,3,4,5,6]);
        $partial = $ps->slice(2, 3);
        
        $expected = [3,4,5];
        foreach ($partial as $v)
            $this->assertSame($v, array_shift($expected));
        
        $expected = [5,6];
        $partial = $ps->slice(4, 7);
        foreach ($partial as $v)
            $this->assertSame($v, array_shift($expected));
        
        $this->expectException(InvalidArgumentException::class);
        $partial = $ps->slice(-3, 7);
    }
    
    public function testSample()
    {
        $input = [1,2,3,4,5,6,8,9,10,11,20,44,22,32,23,87];
        $ps = new PackedArray($input);
        
        $sample = $ps->sample(4);
        $this->assertGreaterThanOrEqual(4, count($sample));
        foreach ($sample as $v)
            $this->assertContains($v, $input);
        
        $sample = $ps->sample(3, 6);
        $this->assertGreaterThanOrEqual(3, count($sample));
        $this->assertLessThanOrEqual(6, count($sample));
        foreach ($sample as $v)
            $this->assertContains($v, $input);
    }
    
    public function testClip()
    {
        $input = [4.3, 4.35, 4.5, 5.7, 6.8, 4.8, 5.1, 3.6];
        $ps = new PackedArray($input);
        $clipped = $ps->clip(4.4);
        
        $expected = [4.4, 4.4, 4.5, 5.7, 6.8, 4.8, 5.1, 4.4];
        foreach ($clipped as $v)
            $this->assertSame($v, array_shift($expected));
        
        $expected = [4.5, 4.5, 4.5, 4.8, 4.8, 4.8, 4.8, 4.5];
        $clipped = $ps->clip(4.5, 4.8);
        foreach ($clipped as $v)
            $this->assertSame($v, array_shift($expected));
    }
    
    public function testSwap()
    {
        $ps = new PackedArray([1,2,3]);
        $ps->swap(0, 2);
        
        $expected = [3,2,1];
        foreach ($ps as $v)
            $this->assertSame($v, array_shift($expected));
    }
    
    public function testSort()
    {
        $input = ['John', 'Sarah', 'Derek', 'Cameron'];
        $ps = new PackedArray($input);
        $ps->sort();
        
        $expected = ['Cameron', 'Derek', 'John', 'Sarah'];
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
        
        $ps->sort(DESCENDING);
        $expected = array_reverse($expected);
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
    }
    
    public function testReverse()
    {
        $input = [1,2,3,4];
        $ps = new PackedArray($input);
        $ps->reverse();
        
        $expected = array_reverse($input);
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
    }
    
    public function testSum()
    {
        $ps = new PackedArray([2,3,1]);
        $this->assertSame(6, $ps->sum());
    }
    
    public function testAvg()
    {
        $input = [1,3,6];
        $ps = new PackedArray($input);
        $this->assertSame(3.33, round($ps->avg(), 2));
    }
    
    public function testMax()
    {
        $input = [2,3,8,1];
        $ps = new PackedArray($input);
        $this->assertSame(8, $ps->max());
        
        $ps = new PackedArray([]);
        $this->assertSame(null, $ps->max());
    }
    
    public function testMin()
    {
        $input = [2,3,8,1];
        $ps = new PackedArray($input);
        $this->assertSame(1, $ps->min());
        
        $ps = new PackedArray([]);
        $this->assertSame(null, $ps->min());
    }
    
    public function testProduct()
    {
        $input = [1,2,3,4];
        $ps = new PackedArray($input);
        $this->assertSame(24, $ps->product());
        
        $ps = new PackedArray([]);
        $this->assertSame(1, $ps->product());
    }
    
    public function testVariance()
    {
        $input = [1,2,3,4];
        $ps = new PackedArray($input);
        $this->assertSame(1.25, round($ps->variance(), 2));
        
        $ps = new PackedArray([]);
        $this->assertSame(0.0, $ps->variance());
    }
    
    public function testRound()
    {
        $input = [1.33456,2.364,3.987,4.3645];
        $ps = new PackedArray($input);
        
        $expected = [1.33, 2.36, 3.99, 4.36];
        foreach ($ps->round(2) as $i => $v)
            $this->assertSame($v, $expected[$i]);
    }
    
    public function testClear()
    {
        $input = [1,2,3,4];
        $ps = new PackedArray($input);
        $ps->clear();
        $this->assertSame(0, count($ps));
    }
    
    public function testAny()
    {
        $input = [1,2,3,4];
        $ps = new PackedArray($input);
        $this->assertSame(true, $ps->any(2));
        $this->assertSame(false, $ps->any(5));
        
        $this->assertSame(true, $ps->any(function($v) {
            return $v == 2 or $v == 3;
        }));
    }
    
    public function testAll()
    {
        $ps = new PackedArray([2,2,2,2]);
        $this->assertSame(true, $ps->all(2));
        
        $ps = new PackedArray([1,2,2,3,4]);
        $this->assertSame(false, $ps->all(2));
        
        $cb = function($v) {
            return $v % 2 == 0;
        };
        $input = [1,2,3,4,5,6,7,8,9];
        $ps = new PackedArray($input);
        $this->assertSame(false, $ps->all($cb));
        
        $input = [2,4,6,8];
        $ps = new PackedArray($input);
        $this->assertSame(true, $ps->all($cb));
    }
    
    public function testFirst()
    {
        $input = [1,2,3,4];
        $ps = new PackedArray($input);
        $this->assertSame(1, $ps->first());
    }
    
    public function testLast()
    {
        $input = [1,2,3,4];
        $ps = new PackedArray($input);
        $this->assertSame(4, $ps->last());
    }
    
    public function testEmpty()
    {
        $ps = new PackedArray([1]);
        $this->assertSame(false, $ps->empty());
        $ps = new PackedArray;
        $this->assertSame(true, $ps->empty());
    }
    
    public function testStartsWith()
    {
        $input = [1,2,3,4];
        $ps = new PackedArray($input);
        $this->assertSame(true, $ps->starts_with(1));
        $this->assertSame(false, $ps->starts_with(2));
    }
    
    public function testEndsWith()
    {
        $input = [1,2,3,4];
        $ps = new PackedArray($input);
        $this->assertSame(true, $ps->ends_with(4));
        $this->assertSame(false, $ps->ends_with(3));
    }
    
    public function testFilter()
    {
        $input = [1,2,3,4,2,3,8,7,5,32,2,5];
        $ps = new PackedArray($input);
        $filtered = $ps->filter(function($v) {
            return $v != 2;
        });
        $this->assertSame(false, $filtered->any(2));
    }
    
    public function testMap()
    {
        $input = [1,2,3,4];
        $ps = new PackedArray($input);
        $mapped = $ps->map(function($v) {
            return $v + 1;
        });
        
        $expected = [2,3,4,5];
        foreach ($mapped as $i => $v)
            $this->assertSame($v, $expected[$i]);
    }
    
    public function testPad()
    {
        $ps = new PackedArray;
        $ps->pad(5, 2);
        $expected = [2,2,2,2,2];
        $this->assertSame(5, count($ps));
        $this->assertSame(true, $ps->all(2));
        
        $ps->pad(-2, 1);
        $expected = array_merge([1,1], $expected);
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
        
        $ps->pad(3, 88);
        $expected[] = 88; $expected[] = 88; $expected[] = 88;
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
    }
    
    public function testNormalise()
    {
        $data = new PackedArray;
        $data = $data->add(0, 5, 10, 15, 20)->normalise();
        $exp = [0, 0.25, 0.5, 0.75, 1];
        foreach ($data as $i => $value)
            $this->assertEquals($value, $exp[$i]);
        
        $data = new PackedArray;
        $data = $data->add(5, 10, 15, 20, 25)->normalise();
        $exp = [0, 0.25, 0.5, 0.75, 1];
        foreach ($data as $i => $value)
            $this->assertEquals($value, $exp[$i]);
        
        $data = new PackedArray;
        $data = $data->add(11.69, 22.78, 3.65)->normalise()->round(2);
        $exp = [0.42, 1.0, 0.0];
        foreach ($data as $i => $value)
            $this->assertEquals($value, $exp[$i]);
                
        $this->expectException(LengthException::class);
        $data = new PackedArray;
        $data->normalise();
        
        $this->expectWarning();
        $data = new PackeArray;
        $data->add(0, 5, 10, 'aaa', 15, 20)->normalise();
    }
}