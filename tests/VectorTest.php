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

class VectorTest extends TestCase
{
    public function testPropulate()
    {
        $input = [1,2,3,4,5,6,7,8,9];
        $ps = vector($input);
        foreach ($ps as $value)
            $this->assertSame($value, array_shift($input));
    }

    public function testGet()
    {
        $input = [1,2,3,4,5,6,7,8,9];
        $ps = vector($input);
        
        $this->assertSame(1, $ps->get(0));
        $this->assertSame(9, $ps->get(8));
        $this->assertSame(4, $ps->get(3));
        
        $this->assertSame(null, $ps->get(-1));
        $this->assertSame(null, $ps->get(9));
        
        $arr = vector([
            ['name' => 'Phil', 'age' => 20],
            ['name' => 'Jane', 'age' => 25],
            ['name' => 'Jill', 'age' => 18],
            ['name' => 'Jane', 'age' => 33], // duplicate name test (should find prior item).
        ]);
        
        $r = $arr->get(function($item) {
            return $item['name'] == 'Jane';
        });
        
        $this->assertEquals(['name' => 'Jane', 'age' => 25], $r);
        
        $r = $arr->get(function($item) {
            return $item['name'] == 'Bob';
        });
        $this->assertNull($r);
    }
    
    public function testAppend()
    {
        $ps = vector();
        $ps->append(1);
        $this->assertSame(1, $ps->get(0));
        
        $ps->append(4);
        $this->assertSame(4, $ps->get(1));
    }
    
    public function testAdd()
    {
        $ps = vector();
        $ps[] = 1;
        $this->assertSame(1, $ps->get(0));
        
        $ps->add(2,3);
        $this->assertSame(2, $ps->get(1));
        $this->assertSame(3, $ps->get(2));
    }
    
    public function testInsert()
    {
        $ps = vector([1,2,4]);
        $ps[2] = 3;
        
        $expected = [1,2,3,4];
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
    }
    
    public function testSet()
    {
        $ps = vector([1,2,3]);
        $ps[1] = 5;
        $this->assertSame(5, $ps->get(1));
        
        $ps[5] = 10; // test adding via index outside of current range.
        $this->assertSame(10, $ps[5]);
    }
    
    public function testRemove()
    {
        $input = [1,2,3,4,5,6,7,8,9];
        $ps = vector($input);
        $ps->remove(1);
        
        $expected = [1,3,4,5,6,7,8,9];
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
        
        $expected = [3,4,5,6,7,8];
        $ps->remove(0);
        $ps->remove($ps->count()-1);
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
    }
    
    public function testPop()
    {
        $ps = vector([1,2,3]);
        $items = $ps->pop(2, true);
        
        $this->assertSame([2,3], $items->array());
        $expected = [1];
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tried to pop a vector that has no elements.');
        vector()->pop();
    }
    
    public function testShift()
    {
        $ps = vector([1,2,3]);
        $items = $ps->shift(2, true);
        
        $this->assertSame([1,2], $items->array());
        $expected = [3];
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tried to shift a vector that has no elements.');
        vector()->shift();
    }
    
    public function testHead()
    {
        $ps = vector([1,2,3,4,5,6,7,8,9]);
        $head = $ps->head(3);
        
        $expected = [1,2,3];
        foreach ($head as $i => $v)
            $this->assertSame($v, $expected[$i]);
        
        $head = $ps->head(0);
        $this->assertSame(count($head), 0);
    }
    
    public function testTail()
    {
        $ps = vector([1,2,3,4,5,6,7,8,9]);
        $tail = $ps->tail(3);
        
        $expected = [7,8,9];
        foreach ($tail as $i => $v)
            $this->assertSame($v, $expected[$i]);
        
        $tail = $ps->tail(0);
        $this->assertSame(count($tail), 0);
    }
    
    public function testSlice()
    {
        $ps = vector([1,2,3,4,5,6]);
        $partial = $ps->slice(2, 3);
        
        $expected = [3,4,5];
        $this->assertSame(3, count($partial));
        foreach ($partial as $v)
            $this->assertSame($v, array_shift($expected));
        
        $expected = [5,6];
        $partial = $ps->slice(4, 7);
        foreach ($partial as $v)
            $this->assertSame($v, array_shift($expected));
        
        $this->expectException(InvalidArgumentException::class);
        $partial = $ps->slice(7, 7);
    }
    
    public function testSample()
    {
        $input = [1,2,3,4,5,6,8,9,10,11,20,44,22,32,23,87];
        $ps = vector($input);
        
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
        $ps = vector($input);
        $clipped = $ps->clip(4.4, 4.8);
        
        $expected = [4.4, 4.4, 4.5, 4.8, 4.8, 4.8, 4.8, 4.4];
        foreach ($clipped as $v)
            $this->assertSame($v, array_shift($expected));
    }
    
    public function testSort()
    {
        $input = [2.3, 1.8, 3.9, 2.4, 2.6, 1.3];
        $ps = vector($input);
        $ps->sort();
        
        $expected = [1.3, 1.8, 2.3, 2.4, 2.6, 3.9];
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
        
        $ps->sort(DESCENDING);
        $expected = array_reverse($expected);
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
    }
    
    public function testKsort()
    {
        $input = ['c' => 1, 'h' => 2, 'a' => 3];
        $ps = vector($input);
        $ps->ksort();
        
        ksort($input);
        $this->assertSame($input, $ps->array());
        
        $ps->ksort(DESCENDING);
        $this->assertSame(array_reverse($input), $ps->array());
    }
    
    public function testKeyedSort()
    {
        $ps = vector([
            ['name' => 'Jane', 'age' => 20, 'height' => 186], ['name' => 'Doug', 'age' => 29, 'height' => 192],
            ['name' => 'Rob', 'age' => 32, 'height' => 187], ['name' => 'Janet', 'age' => 32, 'height' => 183]
        ]);
        $expected = [
            ['name' => 'Doug', 'age' => 29, 'height' => 192], ['name' => 'Jane', 'age' => 20, 'height' => 186], 
            ['name' => 'Janet', 'age' => 32, 'height' => 183], ['name' => 'Rob', 'age' => 32, 'height' => 187]
        ];
        
        $this->assertSame($expected, $ps->keyed_sort('name')->array());
    }
    
    public function testShuffle()
    {
        $input = [1,2,3,4,5,6,7,8,9,10,11,12];
        $ps = vector($input);
        $this->assertNotSame($input, $ps->shuffle()->array());
    }
    
    public function testRotateBack()
    {
        $input = [1,2,3,4,5,6];
        $exp = [6,1,2,3,4,5];
        $this->assertSame($exp, vector($input)->rotate_back()->array());
    }
    
    public function testRotateForward()
    {
        $input = [1,2,3,4,5,6];
        $exp = [2,3,4,5,6,1];
        $this->assertSame($exp, vector($input)->rotate_forward()->array());
    }
    
    public function testReverse()
    {
        $input = [1,2,3,4];
        $ps = vector($input);
        
        $expected = array_reverse($input);
        foreach ($ps->reverse() as $i => $v)
            $this->assertSame($v, $expected[$i]);
    }
    
    public function testFlip()
    {
        $input = ['a' => 1, 'b' => 2, 'c' => 3];
        $ps = vector($input);
        $this->assertSame(array_keys($input), array_values($ps->flip()->array()));
    }
    
    public function testSum()
    {
        $ps = vector([2,3,1]);
        $this->assertSame(6, $ps->sum());
    }
    
    public function testAvg()
    {
        $input = [1,3,6];
        $ps = vector($input);
        $this->assertSame(3.33, round($ps->avg(), 2));
    }
    
    public function testMax()
    {
        $input = [2,3,8,1];
        $ps = vector($input);
        $this->assertSame(8, $ps->max());
        
        $ps = vector([]);
        $this->assertSame(null, $ps->max());
    }
    
    public function testMin()
    {
        $input = [2,3,8,1];
        $ps = vector($input);
        $this->assertSame(1, $ps->min());
        
        $ps = vector([]);
        $this->assertSame(null, $ps->min());
    }
    
    public function testProduct()
    {
        $input = [1,2,3,4];
        $ps = vector($input);
        $this->assertSame(24, $ps->product());
        
        $ps = vector([]);
        $this->assertSame(1, $ps->product());
    }
    
    public function testVariance()
    {
        $input = [1,2,3,4];
        $ps = vector($input);
        $this->assertSame(1.25, round($ps->variance(), 2));
        
        $ps = vector([]);
        $this->assertSame(0.0, $ps->variance());
    }
    
    public function testRound()
    {
        $input = [1.33456,2.364,3.987,4.3645];
        $ps = vector($input);
        
        $expected = [1.33, 2.36, 3.99, 4.36];
        foreach ($ps->round(2) as $i => $v)
            $this->assertSame($v, $expected[$i]);
    }
    
    public function testClear()
    {
        $input = [1,2,3,4];
        $ps = vector($input);
        $ps->clear();
        $this->assertSame(0, count($ps));
    }
    
    public function testAny()
    {
        $input = [1,2,3,4];
        $ps = vector($input);
        $this->assertSame(true, $ps->any(2));
        $this->assertSame(false, $ps->any(5));
        
        $this->assertSame(true, $ps->any(function($v) {
            return $v == 2 or $v == 3;
        }));
    }
    
    public function testAll()
    {
        $ps = vector([2,2,2,2]);
        $this->assertSame(true, $ps->all(2));
        
        $ps = vector([1,2,2,3,4]);
        $this->assertSame(false, $ps->all(2));
        
        $cb = function($v) {
            return $v % 2 == 0;
        };
        $input = [1,2,3,4,5,6,7,8,9];
        $ps = vector($input);
        $this->assertSame(false, $ps->all($cb));
        
        $input = [2,4,6,8];
        $ps = vector($input);
        $this->assertSame(true, $ps->all($cb));
    }
    
    public function testFirst()
    {
        $input = [1,2,3,4];
        $ps = vector($input);
        $this->assertSame(1, $ps->first());
    }
    
    public function testLast()
    {
        $input = [1,2,3,4];
        $ps = vector($input);
        $this->assertSame(4, $ps->last());
    }
    
    public function testEmpty()
    {
        $ps = vector([1]);
        $this->assertSame(false, $ps->empty());
        $ps = vector();
        $this->assertSame(true, $ps->empty());
    }
    
    public function testStartsWith()
    {
        $input = [1,2,3,4];
        $ps = vector($input);
        $this->assertSame(true, $ps->starts_with(1));
        $this->assertSame(false, $ps->starts_with(2));
    }
    
    public function testEndsWith()
    {
        $input = [1,2,3,4];
        $ps = vector($input);
        $this->assertSame(true, $ps->ends_with(4));
        $this->assertSame(false, $ps->ends_with(3));
    }
    
    public function testFilter()
    {
        $input = [1,2,3,4,2,3,8,7,5,32,2,5];
        $ps = vector($input);
        $filtered = $ps->filter(function($v) {
            return $v != 2;
        });
        $this->assertSame(false, $filtered->any(2));
    }
    
    public function testMap()
    {
        $input = [1,2,3,4];
        $ps = vector($input);
        $mapped = $ps->map(function($v) {
            return $v + 1;
        });
        
        $expected = [2,3,4,5];
        foreach ($mapped as $i => $v)
            $this->assertSame($v, $expected[$i]);
    }
    
    public function testPad()
    {
        $ps = vector();
        $ps->pad(5, 2);
        $expected = [2,2,2,2,2];
        $this->assertSame(5, count($ps));
        $this->assertSame(true, $ps->all(2));
        
        $ps->pad(-7, 1);
        $expected = [1,1,2,2,2,2,2];
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
        
        $ps->pad(10, 88);
        $expected = [1,1,2,2,2,2,2,88,88,88];
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
    }
    
    public function testChunk()
    {
        $ps = vector(1,2,3,4,5,6,7);
        $chunks = $ps->chunk(2);
        
        $expected = [ [1,2], [3,4], [5,6], [7] ];
        $this->assertSame(4, count($chunks));
        foreach ($chunks as $i => $c)
            $this->assertSame($c->array(), $expected[$i]);
    }
    
    public function testTrim()
    {
        $ps = vector('Larry ', ' Barry', 'Sam  ');
        $trimmed = $ps->trim();
        
        $expected = ['Larry', 'Barry', 'Sam'];
        foreach ($trimmed as $i => $v)
            $this->assertSame($v, $expected[$i]);
    }
    
    public function testPrepend()
    {
        $ps = vector(1,2,3);
        $ps->prepend('a', 'b', 'c');
        
        $expected = ['a', 'b', 'c', 1,2,3];
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
    }
    
    public function testFill()
    {
        $ps = vector(1,2,3);
        $ps->fill(2, function() {
            return 4;
        });
        $expected = [1,2,3,4,4];
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
    }
    
    public function testPrefill()
    {
        $ps = vector(1,2,3);
        $ps->prefill(2, function() {
            return 4;
        });
        $expected = [4,4,1,2,3];
        foreach ($ps as $i => $v)
            $this->assertSame($v, $expected[$i]);
    }
    
    public function testRemoveRange()
    {
        $ps = vector(1,2,3,4,5,6,7,8,9,10)->remove_range(3,3);
        $this->assertSame([1,2,3,7,8,9,10], $ps->array());
    }
    
    public function testIsset()
    {
        $ps = vector('a', 'b', 'c');
        $this->assertSame(true, $ps->isset(1));
        $this->assertSame(false, $ps->isset(5));
        
        $ps = vector(['name' => 'Doug', 'age' => 29]);
        $this->assertSame(true, $ps->isset('age'));
        $this->assertSame(false, $ps->isset('work'));
    }
    
    public function testKeys()
    {
        $ps = vector(['name' => 'Doug', 'age' => 29]);
        $this->assertSame(['name', 'age'], $ps->keys()->array());
    }
    
    public function testValues()
    {
        $ps = vector([
            ['name' => 'Doug', 'age' => 29], ['name' => 'Jane', 'age' => 20], ['name' => 'Rob', 'age' => 32]
        ]);
        $this->assertSame([29,20,32], $ps->values('age')->array());
    }
    
    public function testUnique()
    {
        $ps = vector(['a',1,2,3,2,2,4,'n',4,8]);
        
        $expected = ['a',1,2,3,4,'n',8];
        $this->assertSame($expected, array_values($ps->unique()->array()));
        
        $ps = vector([
            ['name' => 'Doug', 'age' => 29], ['name' => 'Jane', 'age' => 20], ['name' => 'Rob', 'age' => 32],
            ['name' => 'Janet', 'age' => 32]
        ]);
        
        $this->assertSame([29,20,32], array_values($ps->unique('age')->array()));
    }
    
    public function testFrequency()
    {
        $ps = vector(1,2,3,2,3,4,4,4,2,1,5);
        $expected = [1 => 2, 2 => 3, 3 => 2, 4 => 3, 5 => 1];
        $this->assertSame($expected, $ps->frequency()->array());
    }
    
    public function testPrune()
    {
        $ps = vector('a', '', 'd', 'c', '');
        $this->assertSame(['a', 'd', 'c'], $ps->prune()->array());
    }
    
    public function testMiddle()
    {
        $this->assertSame(2, vector(1,2,3)->middle());
        $this->assertSame(2, vector(1,2,3,4)->middle(true));
        $this->assertSame(3, vector(1,2,3,4)->middle(false));
    }
    
    public function testMedian()
    {
        $this->assertSame(4.5, vector(1,2,3,4,5,6,7,8)->median());
    }
    
    public function testChoose()
    {
        $input = [1,2,3,4,5,6];
        $ps = vector($input);
        $this->assertContains($ps->choose(), $input);
    }
    
    public function testOccursIn()
    {
        $ps = vector('day', 'sunny');
        $this->assertSame(true, $ps->occurs_in('It was a very sunny day'));
        $this->assertSame(false, $ps->occurs_in('The night was long and full of dread'));
    }
    
    public function testIntersect()
    {
        $ps = vector(1,2,3,4,5,6);
        $this->assertSame([3], array_values($ps->intersect([1,3], [2,3,5])->array()));
    }
    
    public function testDiff()
    {
        $ps = vector(1,2,3,4,5,6);
        $this->assertSame([4,6], array_values($ps->diff([1,3], [2,3,5])->array()));
    }
    
    public function testOnlyKeys()
    {
        $ps = vector([
            ['name' => 'Doug', 'age' => 29, 'height' => 192], ['name' => 'Jane', 'age' => 20, 'height' => 186], 
            ['name' => 'Rob', 'age' => 32, 'height' => 187], ['name' => 'Janet', 'age' => 32, 'height' => 183]
        ]);
        $expected = [
            ['age' => 29, 'height' => 192], ['age' => 20, 'height' => 186], 
            ['age' => 32, 'height' => 187], ['age' => 32, 'height' => 183]
        ];
        $this->assertSame($expected, $ps->only_keys('age', 'height')->array());
    }
    
    public function testCumulativeSum()
    {
        $ps = vector(1,2,3,4,5,6);
        $expected = [1,3,6,10,15,21];
        $this->assertSame($expected, $ps->cumsum()->array());
    }
    
    public function testCumulativeProduct()
    {
        $ps = vector(1,2,3,4,5,6);
        $expected = [1,2,6,24,120,720];
        $this->assertSame($expected, $ps->cumproduct()->array());
    }
    
    public function testCumulativeMax()
    {
        $ps = vector(1,2,1,4,3,5,1);
        $expected = [1,2,2,4,4,5,5];
        $this->assertSame($expected, $ps->cummax()->array());
    }
    
    public function testCumulativeMin()
    {
        $ps = vector(7,6,8,4,5,2);
        $expected = [7,6,6,4,4,2];
        $this->assertSame($expected, $ps->cummin()->array());
    }
    
    public function testReduce()
    {
        $ps = vector(1,2,3,4,5,6);
        $r = $ps->reduce(function($carry, $v) {
            return $carry + $v;
        });
        $this->assertSame($r, 21);
        
        $r = $ps->reduce(function($carry, $v) {
            return $carry + $v;
        }, 1);
        $this->assertSame($r, 22);
        
        $r = vector()->reduce(function($carry, $v) {
            return $carry + $v;
        }, 1);
        $this->assertSame($r, 1);
    }
    
    public function testGroupby()
    {
        $ps = vector([
            ['name' => 'Doug', 'age' => 20, 'height' => 192], ['name' => 'Jane', 'age' => 20, 'height' => 186], 
            ['name' => 'Rob', 'age' => 32, 'height' => 187], ['name' => 'Janet', 'age' => 32, 'height' => 183]
        ]);
        $expected = [
            20 => [['name' => 'Doug', 'age' => 20, 'height' => 192], ['name' => 'Jane', 'age' => 20, 'height' => 186]], 
            32 => [['name' => 'Rob', 'age' => 32, 'height' => 187], ['name' => 'Janet', 'age' => 32, 'height' => 183]]
        ];
        
        $grouped = $ps->groupby('age');
        $this->assertSame([20,32], $grouped->keys()->array());
        
        foreach ($grouped as $age => $people)
            $this->assertSame($expected[$age], $people->array());
    }
    
    public function testSplitBy()
    {
        $numbers = vector(1,2,3,4,5,6,7,8,9,10,11);
        $sets = $numbers->splitby(function($v) {
            if ($v == 11)
                return null; // to test omission from results.
            return ($v % 2 == 0) ? 'even' : 'odd';
        });
        
        $this->assertSame(2, count($sets));
        $this->assertSame(true, $sets->isset('even'));
        $this->assertSame(true, $sets->isset('odd'));
        
        $this->assertSame([1,3,5,7,9], $sets['odd']->array());
        $this->assertSame([2,4,6,8,10], $sets['even']->array());
        
        $this->expectException(UnexpectedValueException::class);
        $numbers->splitby(function($v) {
            return [$v];
        });
    }
    
    public function testTranspose()
    {
        $data = vector(
            ['character' => 'Actor A', 'decade' => 1970, 'appearances' => 1],
            ['character' => 'Actor A', 'decade' => 1980, 'appearances' => 2],
            ['character' => 'Actor A', 'decade' => 1990, 'appearances' => 2],
            ['character' => 'Actor A', 'decade' => 2000, 'appearances' => 1],
            ['character' => 'Actor A', 'decade' => 2010, 'appearances' => 1],
    
            ['character' => 'Actor B', 'decade' => 1980, 'appearances' => 1],
            ['character' => 'Actor B', 'decade' => 1990, 'appearances' => 1],
            ['character' => 'Actor B', 'decade' => 2000, 'appearances' => 1],
        );
        $transformed = $data->transpose('decade', ['character' => 'appearances']);
        
        $expected = [
            ['decade' => 1970, 'Actor A' => 1, 'Actor B' => ''],
            ['decade' => 1980, 'Actor A' => 2, 'Actor B' => 1],
            ['decade' => 1990, 'Actor A' => 2, 'Actor B' => 1],
            ['decade' => 2000, 'Actor A' => 1, 'Actor B' => 1],
            ['decade' => 2010, 'Actor A' => 1, 'Actor B' => ''],
        ];
        
        $this->assertSame($expected, $transformed->array());
    }
    
    public function testImplode()
    {
        $this->assertSame('abc', vector('a','b','c')->implode());
        $this->assertSame('a-b-c', vector('a','b','c')->implode('-'));
        
        $ps = vector(
            [1,2,3],
            [4,5,6]
        );
        
        $this->assertSame('1&2&3,4&5&6', $ps->implode(',', '&'));
        $this->assertSame('123,456', $ps->implode(','));
    }
    
    public function testImplodeOnlyKeys()
    {
        $ps = vector([
            ['name' => 'Doug', 'age' => 29, 'height' => 192], ['name' => 'Jane', 'age' => 20, 'height' => 186], 
            ['name' => 'Rob', 'age' => 32, 'height' => 187], ['name' => 'Janet', 'age' => 32, 'height' => 183]
        ]);
        
        $this->assertSame('Doug=29,Jane=20,Rob=32,Janet=32', $ps->implode_only(',', ['name', 'age'], '='));
    }
    
    public function testNormalise()
    {
        $data = vector(0, 5, 10, 15, 20);
        $this->assertEquals([0, 0.25, 0.5, 0.75, 1], $data->normalise()->array());
        
        $data = vector(5, 10, 15, 20, 25);
        $this->assertEquals([0, 0.25, 0.5, 0.75, 1], $data->normalise()->array());
        
        $this->assertEquals([0.42, 1.0, 0.0], vector(11.69, 22.78, 3.65)->normalise()->round(2)->array());
        
        $this->expectException(LengthException::class);
        vector()->normalise();
        
        $this->expectWarning();
        vector(0, 5, 10, 'aaa', 15, 20)->normalise();
    }
}