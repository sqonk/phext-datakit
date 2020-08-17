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

class DataFrameTest extends TestCase
{
    private function _loadFrame()
    {
        $data = [
            ['sepal-length' => '5.1', 'sepal-width' => '3.5', 'petal-length' => '1.4', 'petal-width' =>  '0.2', 'class' => 'Iris-setosa'],
            ['sepal-length' => '7.0', 'sepal-width' => '3.2', 'petal-length' => '4.7', 'petal-width' =>  '1.4', 'class' => 'Iris-versicolor'],
            ['sepal-length' => '6.3', 'sepal-width' => '3.3', 'petal-length' => '6.0', 'petal-width' => '2.5', 'class' => 'Iris-virginica']
        ];
        $df = dataframe($data);
        return [$df, $data];
    }
    
    public function testRows()
    {
        [$df, $data] = $this->_loadFrame();
        foreach (range(0, 2) as $i)
            $this->assertSame($data[$i], $df->row($i));
    }
    
    public function testCount()
    {
        [$df] = $this->_loadFrame();
        
        $this->assertSame(3, $df->count());
    }
    
    public function testSize()
    {
        [$df] = $this->_loadFrame();
        $this->assertSame(3, $df->size('sepal-length'));
        
        $counts = $df->size()[0];
        $exp = ['sepal-length' => 3, 'sepal-width' => 3, 'petal-length' => 3, 'petal-width' =>  3, 'class' => 3];
        $this->assertEquals($exp, $counts);
    }
    
    public function testAddRow()
    {
        [$df, $data] = $this->_loadFrame();
        
        $row = ['sepal-length' => '7.1', 'sepal-width' => '3.5', 'petal-length' => '4.7', 'petal-width' =>  '1.6', 'class' => 'Iris-versicolor'];
        $df->add_row($row);
        
        $this->assertSame($row, $df[LAST_ROW]);
        
        $row = ['sepal-length' => '5.1', 'sepal-width' => '2.6', 'petal-length' => '1.2', 'petal-width' =>  '0.5', 'class' => 'Iris-setosa'];
        $df->add_row($row, 'mykey');
        
        $this->assertSame($row, $df[LAST_ROW]);
    }
    
    public function testSetRow()
    {
        [$df, $data] = $this->_loadFrame();
        
        $row = ['sepal-length' => '7.1', 'sepal-width' => '3.5', 'petal-length' => '4.7', 'petal-width' =>  '1.6', 'class' => 'Iris-versicolor'];
        $df[1] = $row;
        
        $this->assertSame($row, $df[1]);
    }
    
    public function testRemoveRows()
    {
        [$df, $data] = $this->_loadFrame();
        $backup = $df->copy();
        
        // remove single, copy
        $copy = $df->drop_rows(1);
        $this->assertSame(2, $copy->count());
        $this->assertSame($data[0], $copy[0]);
        $this->assertSame($data[2], $copy[2]);
        
        // remove multi, copy
        $multi = $df->drop_rows(1, 2);
        $this->assertSame(1, $multi->count());
        $this->assertSame($data[0], $multi[0]);
        
        // remove single, in place
        $df = $backup->copy();
        $df->drop_rows(1, null, true);
        $this->assertSame(2, $df->count());
        $this->assertSame($data[0], $df[0]);
        $this->assertSame($data[2], $df[2]);
        
        // remove multi, in place
        $multi = $backup->copy();
        $multi = $df->drop_rows(1, 2, true);
        $this->assertSame(1, $multi->count());
        $this->assertSame($data[0], $multi[0]);
    }
    
    public function testHead()
    {
        [$df, $data] = $this->_loadFrame();
        $head = $df->head(2);
        
        $this->assertSame(2, $head->count());
        $this->assertSame($data[0], $head[0]);
        $this->assertSame($data[1], $head[1]);
    }
    
    public function testTail()
    {
        [$df, $data] = $this->_loadFrame();
        $tail = $df->tail(2);
        
        $this->assertSame(2, $tail->count());
        $this->assertSame($data[1], $tail[1]);
        $this->assertSame($data[2], $tail[2]);
    }
    
    public function testSlice()
    {
        [$df, $data] = $this->_loadFrame();
        $tail = $df->slice(1, 2);
        
        $this->assertSame(2, $tail->count());
        $this->assertSame($data[1], $tail[1]);
        $this->assertSame($data[2], $tail[2]);
        
        $this->expectException(InvalidArgumentException::class);
        $df->slice(7, 7);
    }
    
    public function testSample()
    {
        [$df, $data] = $this->_loadFrame();
        
        $sample = $df->sample(1);
        $this->assertGreaterThanOrEqual(1, count($sample));
        foreach ($sample as $v)
            $this->assertContains($v, $data);
        
        $sample = $df->sample(1, 2);
        $this->assertGreaterThanOrEqual(1, count($sample));
        $this->assertLessThanOrEqual(2, count($sample));
        foreach ($sample as $v)
            $this->assertContains($v, $data);
    }
    
    public function testClip()
    {
        [$df, $data] = $this->_loadFrame();
        
        $expMin = [
            ['sepal-length' => '5.5', 'sepal-width' => '5.5', 'petal-length' => '5.5', 'petal-width' =>  '5.5', 'class' => 'Iris-setosa'],
            ['sepal-length' => '7.0', 'sepal-width' => '5.5', 'petal-length' => '5.5', 'petal-width' =>  '5.5', 'class' => 'Iris-versicolor'],
            ['sepal-length' => '6.3', 'sepal-width' => '5.5', 'petal-length' => '6.0', 'petal-width' => '5.5', 'class' => 'Iris-virginica']
        ];
        $expBoth = [
            ['sepal-length' => '5.5', 'sepal-width' => '5.5', 'petal-length' => '5.5', 'petal-width' =>  '5.5', 'class' => 'Iris-setosa'],
            ['sepal-length' => '6.5', 'sepal-width' => '5.5', 'petal-length' => '5.5', 'petal-width' =>  '5.5', 'class' => 'Iris-versicolor'],
            ['sepal-length' => '6.3', 'sepal-width' => '5.5', 'petal-length' => '6.0', 'petal-width' => '5.5', 'class' => 'Iris-virginica']
        ];
        
        // copy
        $this->assertEquals([5.5,7.0,6.3], $df->clip(5.5, null, 'sepal-length')->values('sepal-length'));
        $this->assertEquals([5.5,6.5,6.3], $df->clip(5.5, 6.5, 'sepal-length')->values('sepal-length'));
        $this->assertEquals($expMin, $df->clip(5.5, null)->data());
        $this->assertEquals($expBoth, $df->clip(5.5, 6.5)->data());
        
        // in place
        $this->assertEquals([5.5,7.0,6.3], $df->copy()->clip(5.5, null, 'sepal-length', true)->values('sepal-length'));
        $this->assertEquals([5.5,6.5,6.3], $df->copy()->clip(5.5, 6.5, 'sepal-length', true)->values('sepal-length'));
        $this->assertEquals($expMin, $df->copy()->clip(5.5, null, null, true)->data());
        $this->assertEquals($expBoth, $df->copy()->clip(5.5, 6.5, null, true)->data());
    }
    
    public function testPrune()
    {
        [$df, $data] = $this->_loadFrame();
        
        $expMin = [
            ['class' => 'Iris-setosa'],
            ['sepal-length' => '7.0', 'class' => 'Iris-versicolor'],
            ['sepal-length' => '6.3', 'petal-length' => '6.0', 'class' => 'Iris-virginica']
        ];
        $expBoth = [
            ['class' => 'Iris-setosa'],
            ['class' => 'Iris-versicolor'],
            ['sepal-length' => '6.3', 'petal-length' => '6.0', 'class' => 'Iris-virginica']
        ];
        
        // copy
        $this->assertEquals([7.0,6.3], $df->prune(5.5, null, 'sepal-length')->values('sepal-length'));
        $this->assertEquals([6.3], $df->prune(5.5, 6.5, 'sepal-length')->values('sepal-length'));
        $this->assertEquals($expMin, $df->prune(5.5, null)->data());
        $this->assertEquals($expBoth, $df->prune(5.5, 6.5)->data());
        
        // in place
        $this->assertEquals([7.0,6.3], $df->copy()->prune(5.5, null, 'sepal-length', true)->values('sepal-length'));
        $this->assertEquals([6.3], $df->copy()->prune(5.5, 6.5, 'sepal-length', true)->values('sepal-length'));
        $this->assertEquals($expMin, $df->copy()->prune(5.5, null, null, true)->data());
        $this->assertEquals($expBoth, $df->copy()->prune(5.5, 6.5, null, true)->data());
    }
    
    public function testSort()
    {
        [$df, $data] = $this->_loadFrame();
        
        $data = [
            ['sepal-length' => '5.1', 'sepal-width' => '3.5', 'petal-length' => '1.4', 'petal-width' =>  '0.2', 'class' => 'Iris-setosa'],
            ['sepal-length' => '7.0', 'sepal-width' => '3.2', 'petal-length' => '4.7', 'petal-width' =>  '1.4', 'class' => 'Iris-versicolor'],
            ['sepal-length' => '6.3', 'sepal-width' => '3.3', 'petal-length' => '6.0', 'petal-width' => '2.5', 'class' => 'Iris-virginica']
        ];
        
        $this->assertEquals($data, $df->sort('class', ASCENDING)->reindex_rows(range(0, count($df)))->data());
        
        $data = array_reverse($data);
        $this->assertEquals($data, $df->sort('class', DESCENDING)->reindex_rows(range(0, count($df)))->data());
    }
    
    public function testUsort()
    {
        [$df, $data] = $this->_loadFrame();
        
        $data = [
            ['sepal-length' => '5.1', 'sepal-width' => '3.5', 'petal-length' => '1.4', 'petal-width' =>  '0.2', 'class' => 'Iris-setosa'],
            ['sepal-length' => '7.0', 'sepal-width' => '3.2', 'petal-length' => '4.7', 'petal-width' =>  '1.4', 'class' => 'Iris-versicolor'],
            ['sepal-length' => '6.3', 'sepal-width' => '3.3', 'petal-length' => '6.0', 'petal-width' => '2.5', 'class' => 'Iris-virginica']
        ];
        
        $this->assertEquals($data, $df->usort(function($a, $b) {
            return $a <=> $b;
        }, 'class')
            ->reindex_rows(range(0, count($df)))->data());
    }
    
    public function testSum()
    {
        [$df] = $this->_loadFrame();
        
        $this->assertEquals(18.4, $df->sum('sepal-length'));
        
        $exp = [
            ['sepal-length' => '18.4', 'sepal-width' => '10.0', 'petal-length' => '12.1', 'petal-width' =>  '4.1', 'class' => 'Iris-setosa']
        ];
        $this->assertEquals($exp, $df->sum()->data());
    }
    
    public function testAvg()
    {
        [$df] = $this->_loadFrame();
        
        $this->assertEquals(6.13, round($df->avg('sepal-length'), 2));
        
        $exp = [
            ['sepal-length' => '6.13', 'sepal-width' => '3.33', 'petal-length' => '4.03', 'petal-width' =>  '1.37']
        ];
        $this->assertEquals($exp, $df->avg()->round(2)->data());
    }
    
   public function testMax()
    {
        [$df] = $this->_loadFrame();
        
        $this->assertEquals(7.0, $df->max('sepal-length'));
        
        $exp = [
            ['sepal-length' => 7.0, 'sepal-width' => 3.5, 'petal-length' => 6.0, 'petal-width' =>  2.5]
        ];
        $this->assertEquals($exp, $df->max()->data());
    }
    
    public function testMin()
    {
        [$df] = $this->_loadFrame();
        
        $this->assertEquals(5.1, $df->min('sepal-length'));
        
        $exp = [
            ['sepal-length' => 5.1, 'sepal-width' => 3.2, 'petal-length' => 1.4, 'petal-width' =>  0.2]
        ];
        $this->assertEquals($exp, $df->min()->data());
    }
    
    public function testProduct()
    {
        [$df] = $this->_loadFrame();
        
        $this->assertEquals(224.91, $df->product('sepal-length'));
        
        $exp = [
            ['sepal-length' => 224.91, 'sepal-width' => 36.96, 'petal-length' => 39.48, 'petal-width' =>  0.7]
        ];
        $this->assertEquals($exp, $df->product()->data());
    }
    
   public function testVariance()
    {
        [$df] = $this->_loadFrame();
        
        $this->assertEquals(0.62, round($df->variance('sepal-length'), 2));
        
        $exp = [
            ['sepal-length' => 0.62, 'sepal-width' => 0.02, 'petal-length' => 3.75, 'petal-width' =>  0.88]
        ];
        $this->assertEquals($exp, $df->variance()->round(2)->data());
    }
    
    public function testMedian()
    {
        [$df] = $this->_loadFrame();
        
        $this->assertEquals(224.91, $df->product('sepal-length'));
        
        $exp = [
            ['sepal-length' => 6.3, 'sepal-width' => 3.3, 'petal-length' => 4.7, 'petal-width' =>  1.4]
        ];
        $this->assertEquals($exp, $df->median()->data());
    }
    
    public function testQuantile()
    {
        [$df] = $this->_loadFrame();
        
        $this->assertEquals(5.7, $df->quantile(0.25, 'sepal-length'));
        
        $exp = [
            ['sepal-length' => 5.7, 'sepal-width' => 3.25, 'petal-length' => 3.05, 'petal-width' =>  0.8]
        ];
        $this->assertEquals($exp, $df->quantile(0.25)->data());
        
        $exp = [
            ['sepal-length' => 6.3, 'sepal-width' => 3.3, 'petal-length' => 4.7, 'petal-width' =>  1.4]
        ];
        $this->assertEquals($exp, $df->quantile(0.5)->data());
        
        $exp = [
            ['sepal-length' => 6.65, 'sepal-width' => 3.4, 'petal-length' => 5.35, 'petal-width' =>  1.95]
        ];
        $this->assertEquals($exp, $df->quantile(0.75)->data());
    }
    
    public function testSpearmanCorrelation()
    {
        [$df] = $this->_loadFrame();
        
        $exp = [
            [1, -1, 0.5, 0.5],
            [-1, 1, -0.5, -0.5],
            [0.5, -0.5, 1, 1],
            [0.5, -0.5, 1, 1]
        ];
        
        $this->assertSame($exp, $df->corr('spearman', ['sepal-length', 'sepal-width', 'petal-length', 'petal-width'])->data());
    }
    
    public function testPearsonCorrelation()
    {
        [$df] = $this->_loadFrame();
        
        $exp = [
            [1.0, -1.0, 0.8, 0.64],
            [-1.0, 1.0, -0.82, -0.67],
            [0.8, -0.82, 1.0, 0.98],
            [0.64, -0.67, 0.98, 1.0]
        ];
        
        $corr = $df->corr('pearson', ['sepal-length', 'sepal-width', 'petal-length', 'petal-width'])->round(2);
        $this->assertSame($exp, $corr->data());
    }
    
    public function testAny()
    {
        [$df] = $this->_loadFrame();
        
        $this->assertSame(true, $df->any(1.4, 'petal-length'));
        $this->assertSame(false, $df->any(1.4, 'sepal-length'));
        $this->assertSame(true, $df->any(1.4));
        $this->assertSame(false, $df->any(1.5));
    }
    
    public function testAll()
    {
        $data = [
            ['sepal-length' => '5.1', 'sepal-width' => '3.5', 'petal-length' => '1.4', 'petal-width' =>  '0.2', 'class' => 'Iris-setosa'],
            ['sepal-length' => '7.0', 'sepal-width' => '3.5', 'petal-length' => '4.7', 'petal-width' =>  '1.4', 'class' => 'Iris-versicolor'],
            ['sepal-length' => '6.3', 'sepal-width' => '3.5', 'petal-length' => '6.0', 'petal-width' => '2.5', 'class' => 'Iris-virginica']
        ];
        $df = dataframe($data);
        
        $this->assertSame(false, $df->all(4.7, 'petal-length'));
        $this->assertSame(true, $df->all(3.5, 'sepal-width'));
        $this->assertSame(false, $df->all(4.7));
    }
    
    
    public function testFilter()
    {
        [$df] = $this->_loadFrame();
        
        $exp = [
            ['sepal-length' => '5.1', 'sepal-width' => '3.5', 'petal-length' => '1.4', 'petal-width' =>  '0.2', 'class' => 'Iris-setosa'],
        ];
        $filtered = $df->filter(function($v, $col) {
            return $v == 5.1;
        }, 'sepal-length', 'petal-length');
        $this->assertEquals($exp, $filtered->data());
    }
    
    public function testUnanfilter()
    {
        [$df] = $this->_loadFrame();
        
        $exp = [
            ['sepal-length' => '5.1', 'sepal-width' => '3.5', 'petal-length' => '1.4', 'petal-width' =>  '0.2', 'class' => 'Iris-setosa'],
        ];
        $filtered = $df->unanfilter(function($v, $col) {
            return $v == 5.1;
        }, 'sepal-length', 'petal-length');
        $this->assertEquals(null, $filtered);
        
        $filtered = $df->unanfilter(function($v, $col) {
            return ($col == 'sepal-length') ? ($v == 5.1) : ($v == 1.4);
        }, 'sepal-length', 'petal-length');
        $this->assertNotNull($filtered);
        $this->assertEquals($exp, $filtered->data());
    }
    
    public function testUfilter()
    {
        [$df] = $this->_loadFrame();
        
        $exp = [
            ['sepal-length' => '5.1', 'sepal-width' => '3.5', 'petal-length' => '1.4', 'petal-width' =>  '0.2', 'class' => 'Iris-setosa'],
        ];
        $filtered = $df->ufilter(function($row) {
            return $row['sepal-length'] == 5.1;
        });
        $this->assertEquals($exp, $filtered->data());
    }
    
    public function testIndexes()
    {
        [$df] = $this->_loadFrame();
        $this->assertSame([0,1,2], $df->indexes());
    }
    
    public function testValues()
    {
        [$df, $data] = $this->_loadFrame();
        
        $this->assertEquals([5.1, 7.0, 6.3], $df->values('sepal-length'));
    }
    
    public function testShape()
    {
        [$df] = $this->_loadFrame();
        [$rows, $cols] = $df->shape();
        $this->assertSame(3, $rows);
        $this->assertSame(5, $cols);
    }
    
    public function testReport()
    {
        $col2 = "\n     	sepal-width	sepal-length\n_____	___________	____________\n0    	        3.5	         5.1\n1    	        3.2	         7.0\n2    	        3.3	         6.3";

        $all = "\n     	sepal-length	sepal-width	petal-length	petal-width	          class\n_____	____________	___________	____________	___________	_______________\n0    	         5.1	        3.5	         1.4	        0.2	    Iris-setosa\n1    	         7.0	        3.2	         4.7	        1.4	Iris-versicolor\n2    	         6.3	        3.3	         6.0	        2.5	 Iris-virginica";
        
        [$df, $data] = $this->_loadFrame();
        $this->assertEquals($col2, $df->report('sepal-width', 'sepal-length'));
        
        $this->assertEquals($all, $df->report());
    }
    
    public function testCumulativeSum()
    {
        [$df] = $this->_loadFrame();
        
        $this->assertEquals([5.1,12.1,18.4], $df->cumsum('sepal-length'));
        
        $exp = [
            [5.1,3.5,1.4,0.2,'Iris-setosa'],
            [12.1,6.7,6.1,1.6,'Iris-versicolor'],
            [18.4,10,12.1,4.1,'Iris-virginica']
        ];
        foreach ($df->cumsum() as $i => $row)
            $this->assertEquals($exp[$i], array_values($row));
    }
    
    public function testCumulativeProduct()
    {
        [$df] = $this->_loadFrame();
        
        $this->assertEquals([5.1,35.7,224.91], $df->cumproduct('sepal-length'));
        
        $exp = [
            [5.1,3.5,1.4,0.2,'Iris-setosa'],
            [35.7,11.2,6.58,0.28,'Iris-versicolor'],
            [224.91,36.96,39.48,0.7,'Iris-virginica']
        ];
        foreach ($df->cumproduct() as $i => $row)
            $this->assertEquals($exp[$i], array_values($row));
    }
    
    public function testCumulativeMax()
    {
        [$df] = $this->_loadFrame();
        
        $this->assertEquals([5.1,7.0,7.0], $df->cummax('sepal-length'));
        
        $exp = [
            [5.1,3.5,1.4,0.2,'Iris-setosa'],
            [7.0,3.5,4.7,1.4,'Iris-versicolor'],
            [7.0,3.5,6.0,2.5,'Iris-virginica']
        ];
        foreach ($df->cummax() as $i => $row)
            $this->assertEquals($exp[$i], array_values($row));
    }
    
    public function testCumulativeMin()
    {
        [$df] = $this->_loadFrame();
        
        $this->assertEquals([5.1,5.1,5.1], $df->cummin('sepal-length'));
        
        $exp = [
            [5.1,3.5,1.4,0.2,'Iris-setosa'],
            [5.1,3.2,1.4,0.2,'Iris-versicolor'],
            [5.1,3.2,1.4,0.2,'Iris-virginica']
        ];
        foreach ($df->cummin() as $i => $row)
            $this->assertEquals($exp[$i], array_values($row));
    }
    
    
    public function testGroupby()
    {
        $df = dataframe([
            ['name' => 'Falcon', 'Animal' => 'bird', 'Age' => 8, 'size' => 'big'],
            ['name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
            ['name' => 'Goat', 'Animal' => 'mammal', 'Age' => 12, 'size' => 'small'],
            ['name' => 'Possum', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big']
        ]);
        $g = $df->groupby('Animal');
        
        $exp = [
            0 => [
                0 => ['name' => 'Falcon', 'Animal' => 'bird', 'Age' => 8, 'size' => 'big'],
                1 => ['name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
            ],
            [
                2 => ['name' => 'Goat', 'Animal' => 'mammal', 'Age' => 12, 'size' => 'small'],
                3 => ['name' => 'Possum', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big']
            ]
        ];
        
        $this->assertSame($exp, array_values($g->data()));
    }
    
    public function testTranspose()
    {
        $df = dataframe([
            ['character' => 'Actor A', 'decade' => 1970, 'appearances' => 1],
            ['character' => 'Actor A', 'decade' => 1980, 'appearances' => 2],
            ['character' => 'Actor A', 'decade' => 1990, 'appearances' => 2],
            ['character' => 'Actor A', 'decade' => 2000, 'appearances' => 1],
            ['character' => 'Actor A', 'decade' => 2010, 'appearances' => 1],
    
            ['character' => 'Actor B', 'decade' => 1980, 'appearances' => 1],
            ['character' => 'Actor B', 'decade' => 1990, 'appearances' => 1],
            ['character' => 'Actor B', 'decade' => 2000, 'appearances' => 1],
        ]);
        $transformed = $df->transpose('decade', ['character' => 'appearances']);
        
        $expected = [
            ['decade' => 1970, 'Actor A' => 1, 'Actor B' => ''],
            ['decade' => 1980, 'Actor A' => 2, 'Actor B' => 1],
            ['decade' => 1990, 'Actor A' => 2, 'Actor B' => 1],
            ['decade' => 2000, 'Actor A' => 1, 'Actor B' => 1],
            ['decade' => 2010, 'Actor A' => 1, 'Actor B' => ''],
        ];
        
        $this->assertSame($expected, $transformed->data());
    }
    
    
}