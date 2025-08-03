<?php
#declare(strict_types=1);
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

require __DIR__ . '/TCFuncs.php';

use PHPUnit\Framework\TestCase;
use sqonk\phext\datakit\Importer as import;
use sqonk\phext\datakit\math;
use sqonk\phext\datakit\DataFrame;

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

  public function testCreateFromVerticalDataset()
  {
    $data = [
      'col 1' => range(1, 3),
      'col 2' => ['a', 'b', 'c']
    ];
    $exp = [
      ['col 1' => 1, 'col 2' => 'a'],
      ['col 1' => 2, 'col 2' => 'b'],
      ['col 1' => 3, 'col 2' => 'c']
    ];

    $df = dataframe($data, null, true);

    $this->assertSame($exp, $df->data());
  }

  public function testRows()
  {
    [$df, $data] = $this->_loadFrame();
    foreach (range(0, 2) as $i) {
      $this->assertSame($data[$i], $df->row($i));
    }
  }

  public function testCount()
  {
    [$df] = $this->_loadFrame();

    $this->assertSame(3, $df->count());
  }

  public function testRound(): void
  {
    $data = [
      ['a' => 1.2573, 'b' => 4.331]
    ];

    $df = dataframe($data);

    # test round copy (original unmodified)
    $this->assertEquals([['a' => '1.3', 'b' => '4.3']], $df->round(1)->data());

    # test in-place rounding
    $df->round(1, inPlace: true);
    $this->assertEquals([['a' => '1.3', 'b' => '4.3']], $df->data());
  }

  public function testRolling(): void
  {
    $data = [
      'a' => [1, 2, 3, 4, 5, 6],
      'b' => [6, 5, 4, 3, 2, 1]
    ];
    $df = new DataFrame($data, isVerticalDataSet: true);

    # test rolling across a singular column, akin to the Vector implementation of the same method.
    $exp = [
      [1, 2],
      [2, 3],
      [3, 4],
      [4, 5],
      [5, 6]
    ];
    $result = $df->rolling(window: 2, columns: 'a', callback: function ($set, $i, $col) use ($exp) {
      $this->assertEquals($set->array(), $exp[$i - 1], "index $i");
      return $set->sum();
    });
    $exp = [3, 5, 7, 9, 11];
    $this->assertEquals($result->values(columns: 'a'), $exp);

    # test same as above but with a minimum observation of 1.
    $exp = [
      [1],
      [1, 2],
      [2, 3],
      [3, 4],
      [4, 5],
      [5, 6]
    ];
    $result = $df->rolling(window: 2, columns: 'a', minObservations: 1, callback: function ($set, $i, $col) use ($exp) {
      $this->assertEquals($set->array(), $exp[$i], "index $i");
      return $set->sum();
    });
    $exp = [1, 3, 5, 7, 9, 11];
    $this->assertEquals($result->values(columns: 'a'), $exp);

    # test specific indexes with both columns
    $exp = [
      ['a' => [1, 2], 'b' => [6, 5]],
      ['a' => [2, 3], 'b' => [5, 4]],
      ['a' => [3, 4], 'b' => [4, 3]],
      ['a' => [4, 5], 'b' => [3, 2]],
      ['a' => [5, 6], 'b' => [2, 1]]
    ];
    $result = $df->rolling(window: 2, callback: function ($set, $i, $col) use ($exp) {
      $this->assertEquals($exp[$i - 1][$col], $set->array(), "index $i,$col");
      return $set->sum();
    });
    $this->assertEquals([3, 5, 7, 9, 11], $result->values(columns: 'a'), 'rolling sums for column A');
    $this->assertEquals([11, 9, 7, 5, 3], $result->values(columns: 'b'), 'rolling sums for column B');

    $data = [
      'a' => [1, 2, 3, 4, 5, 6],
      'b' => [6, 5, 4, 3, 2, 1],
      'c' => [2, 2, 2, 2, 2, 2]
    ];
    $df = new DataFrame($data, isVerticalDataSet: true);
    $exp = [
      ['b' => [1, 6], 'c' => [6, 2]],
      ['b' => [2, 5], 'c' => [5, 2]],
      ['b' => [3, 4], 'c' => [4, 2]],
      ['b' => [4, 3], 'c' => [3, 2]],
      ['b' => [5, 2], 'c' => [2, 2]],
      ['b' => [6, 1], 'c' => [1, 2]]
    ];

    # running horizontal
    $result = $df->rolling(window: 2, runHorizontal: true, callback: function ($set, $i, $col) use ($exp) {
      $this->assertEquals($exp[$i][$col], $set->array(), "index $i,$col");
      return $set->sum();
    });
    // Column A will be all null, which default a call to values will return an empty array.
    $this->assertEquals([], $result->values(columns: 'a'), 'rolling H sums for column A');
    $this->assertEquals([7, 7, 7, 7, 7, 7], $result->values(columns: 'b'), 'rolling H sums for column B');
    $this->assertEquals([8, 7, 6, 5, 4, 3], $result->values(columns: 'c'), 'rolling H sums for column C');

    # horizontal with specific indexes
    $result = $df->rolling(
      window: 2,
      runHorizontal: true,
      indexes: [0, 3, 5],
      callback: function ($set, $i, $col) use ($exp) {
        $this->assertEquals($exp[$i][$col], $set->array(), "index $i,$col");
        return $set->sum();
      }
    );
    $this->assertEquals([], $result->values(columns: 'a'), 'rolling HI sums for column A');
    $this->assertEquals([7, 7, 7], $result->values(columns: 'b'), 'rolling HI sums for column B');
    $this->assertEquals([8, 5, 3], $result->values(columns: 'c'), 'rolling HI sums for column C');

    # validation errors and exceptions.

    $windowSize = 0;
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage("window must be a number greater than 0 ($windowSize given)");
    $df->rolling($windowSize, fn($v) => $v);
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
    [$df, $_] = $this->_loadFrame();

    $row = ['sepal-length' => '7.1', 'sepal-width' => '3.5', 'petal-length' => '4.7', 'petal-width' =>  '1.6', 'class' => 'Iris-versicolor'];
    $df->add_row($row);

    $this->assertSame($row, $df[LAST_ROW]);

    $row = ['sepal-length' => '5.1', 'sepal-width' => '2.6', 'petal-length' => '1.2', 'petal-width' =>  '0.5', 'class' => 'Iris-setosa'];
    $df->add_row($row, 'mykey');

    $this->assertSame($row, $df[LAST_ROW]);
  }

  public function testAddColumn()
  {
    [$df] = $this->_loadFrame();

    $df->add_column('Test', function ($row, $index) {
      return 'test';
    });

    foreach ($df as $row) {
      $this->assertSame('test', $row['Test']);
    }
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

  public function testRemoveColumns()
  {
    [$df] = $this->_loadFrame();

    // copy
    $copy = $df->drop_columns(['class']);
    foreach ($copy as $row) {
      $this->assertSame(false, array_key_exists('Test', $row));
    }

    // in place
    $df->drop_columns(['class']);
    foreach ($df as $row) {
      $this->assertSame(false, array_key_exists('Test', $row));
    }
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
    foreach ($sample as $v) {
      $this->assertContains($v, $data);
    }

    $sample = $df->sample(1, 2);
    $this->assertGreaterThanOrEqual(1, count($sample));
    $this->assertLessThanOrEqual(2, count($sample));
    foreach ($sample as $v) {
      $this->assertContains($v, $data);
    }
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
    $this->assertEquals([5.5, 7.0, 6.3], $df->clip(5.5, null, 'sepal-length')->values('sepal-length'));
    $this->assertEquals([5.5, 6.5, 6.3], $df->clip(5.5, 6.5, 'sepal-length')->values('sepal-length'));
    $this->assertEquals($expMin, $df->clip(5.5, null)->data());
    $this->assertEquals($expBoth, $df->clip(5.5, 6.5)->data());

    // in place
    $this->assertEquals([5.5, 7.0, 6.3], $df->copy()->clip(5.5, null, 'sepal-length', true)->values('sepal-length'));
    $this->assertEquals([5.5, 6.5, 6.3], $df->copy()->clip(5.5, 6.5, 'sepal-length', true)->values('sepal-length'));
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
    $this->assertEquals([7.0, 6.3], $df->prune(5.5, null, 'sepal-length')->values('sepal-length'));
    $this->assertEquals([6.3], $df->prune(5.5, 6.5, 'sepal-length')->values('sepal-length'));
    $this->assertEquals($expMin, $df->prune(5.5, null)->data());
    $this->assertEquals($expBoth, $df->prune(5.5, 6.5)->data());

    // in place
    $this->assertEquals([7.0, 6.3], $df->copy()->prune(5.5, null, 'sepal-length', true)->values('sepal-length'));
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

    $this->assertEquals($data, $df->usort(function ($a, $b) {
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

  public function testAbs()
  {
    $data = [
      ['sepal-length' => 5.1, 'sepal-width' => -3.5, 'petal-length' => 1.4, 'petal-width' =>  0.2, 'class' => 'Iris-setosa'],
      ['sepal-length' => -7.0, 'sepal-width' => 3.2, 'petal-length' => 4.7, 'petal-width' =>  -1.4, 'class' => 'Iris-versicolor'],
      ['sepal-length' => 6.3, 'sepal-width' => 3.3, 'petal-length' => -6.0, 'petal-width' => -2.5, 'class' => 'Iris-virginica']
    ];
    $df = dataframe($data);

    // return copy
    foreach ($df->abs('sepal-length') as $i => $row) {
      $this->assertEquals(abs((float)$data[$i]['sepal-length']), (float)$row['sepal-length']);
    }

    foreach ($df->abs() as $i => $row) {
      foreach ($row as $key => $v) {
        if (is_numeric($row[$key])) {
          $this->assertEquals(abs($data[$i][$key]), $v);
        }
      }
    }

    // in place
    foreach ($df->abs('sepal-length', true) as $i => $row) {
      $this->assertEquals(abs($data[$i]['sepal-length']), $row['sepal-length']);
    }

    foreach ($df->abs(null, true) as $i => $row) {
      foreach ($row as $key => $v) {
        if (is_numeric($row[$key])) {
          $this->assertEquals(abs($data[$i][$key]), $v);
        }
      }
    }
  }

  public function testStd()
  {
    [$df] = $this->_loadFrame();

    $this->assertEquals(0.96, round($df->std(true, 'sepal-length'), 2));
    $this->assertEquals(0.78, round($df->std(false, 'sepal-length'), 2));

    $exp = [0.96, 0.15, 2.37, 1.15];
    $this->assertEquals($exp, array_values($df->std(true, 'sepal-length', 'sepal-width', 'petal-length', 'petal-width')->round(2)[0]));

    $exp = [0.78, 0.12, 1.94, 0.94];
    $this->assertEquals($exp, array_values($df->std(false, 'sepal-length', 'sepal-width', 'petal-length', 'petal-width')->round(2)[0]));
  }

  public function testMax()
  {
    [$df] = $this->_loadFrame();

    $this->assertEquals(7.0, math::nf_round($df->max('sepal-length'), 1));

    $exp = [
      ['sepal-length' => 7.0, 'sepal-width' => 3.5, 'petal-length' => 6.0, 'petal-width' =>  2.5]
    ];
    $this->assertEquals($exp, $df->max()->data());
  }

  public function testMin()
  {
    [$df] = $this->_loadFrame();
    $df->round(1, inPlace: true);

    $this->assertEquals(5.1, math::nf_round($df->min('sepal-length'), 1));

    $exp = [
      ['sepal-length' => 5.1, 'sepal-width' => 3.2, 'petal-length' => 1.4, 'petal-width' =>  0.2]
    ];
    $this->assertEquals($exp, $df->min()->data());
  }

  public function testProduct()
  {
    [$df] = $this->_loadFrame();
    $df->round(1, inPlace: true);

    $this->assertEquals('224.91', round($df->product('sepal-length'), 2));

    $exp = [
      ['sepal-length' => '224.91', 'sepal-width' => '36.96', 'petal-length' => '39.48', 'petal-width' =>  '0.70']
    ];
    $this->assertEquals($exp, $df->product()->round(2)->data());
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
    $df->round(2, inPlace: true);

    $this->assertEquals(224.91, round($df->product('sepal-length'), 2));

    $exp = [
      ['sepal-length' => 6.30, 'sepal-width' => 3.30, 'petal-length' => 4.70, 'petal-width' =>  1.40]
    ];
    $this->assertEquals($exp, $df->median()->data());
  }

  public function testQuantile()
  {
    [$df] = $this->_loadFrame();
    $df->round(2, inPlace: true);

    $this->assertEquals(5.70, round($df->quantile(0.25, 'sepal-length'), 2));

    $exp = [
      ['sepal-length' => 5.70, 'sepal-width' => 3.25, 'petal-length' => 3.05, 'petal-width' =>  0.80]
    ];
    $this->assertEquals($exp, $df->quantile(0.25)->round(2)->data());

    $exp = [
      ['sepal-length' => 6.30, 'sepal-width' => 3.30, 'petal-length' => 4.70, 'petal-width' =>  1.40]
    ];
    $this->assertEquals($exp, $df->quantile(0.5)->round(2)->data());

    $exp = [
      ['sepal-length' => 6.65, 'sepal-width' => 3.40, 'petal-length' => 5.35, 'petal-width' =>  1.95]
    ];
    $this->assertEquals($exp, $df->quantile(0.75)->round(2)->data());
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

    $corr = $df->corr(COR_SPEARMAN, ['sepal-length', 'sepal-width', 'petal-length', 'petal-width'])->data();
    $i = 0;
    foreach ($corr as $row) {
      $this->assertSame($exp[$i], array_values($row));
      $i++;
    }
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

    $corr = $df->corr(COR_PEARSON, ['sepal-length', 'sepal-width', 'petal-length', 'petal-width'])->round(2);
    $i = 0;
    foreach ($corr as $row) {
      $this->assertEquals($exp[$i], array_values($row));
      $i++;
    }
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

  public function testFilterByValue(): void
  {
    [$df] = $this->_loadFrame();

    $exp = [
      ['sepal-length' => '5.1', 'sepal-width' => '3.5', 'petal-length' => '1.4', 'petal-width' =>  '0.2', 'class' => 'Iris-setosa'],
    ];

    $filtered = $df->filter_by_value(5.1, 'sepal-length');
    $this->assertEquals(expected: $exp, actual: $filtered->data());

    $this->assertEquals(expected: null, actual: $df->filter_by_value(5.1, 'sepal-length', strictCompare: true));

    $filtered = $df->filter_by_value('5.1', 'sepal-length', strictCompare: true);
    $this->assertEquals(expected: $exp, actual: $filtered->data());
  }

  public function testFilter()
  {
    [$df] = $this->_loadFrame();

    $exp = [
      ['sepal-length' => '5.1', 'sepal-width' => '3.5', 'petal-length' => '1.4', 'petal-width' =>  '0.2', 'class' => 'Iris-setosa'],
    ];
    $filtered = $df->filter(function ($v, $col) {
      return $v == 5.1;
    }, 'sepal-length', 'petal-length');
    $this->assertEquals($exp, $filtered->data());

    $filtered = $df->filter(function ($v, $col) {
      return $v == 59;
    }, 'sepal-length', 'petal-length');
    $this->assertEquals(null, $filtered);
  }

  public function testUnanfilter()
  {
    [$df] = $this->_loadFrame();

    $exp = [
      ['sepal-length' => '5.1', 'sepal-width' => '3.5', 'petal-length' => '1.4', 'petal-width' =>  '0.2', 'class' => 'Iris-setosa'],
    ];
    $filtered = $df->unanfilter(function ($v, $col) {
      return $v == 5.1;
    }, 'sepal-length', 'petal-length');
    $this->assertEquals(null, $filtered);

    $filtered = $df->unanfilter(function ($v, $col) {
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
    $filtered = $df->ufilter(function ($row) {
      return $row['sepal-length'] == 5.1;
    });
    $this->assertEquals($exp, $filtered->data());

    $filtered = $df->ufilter(function ($row) {
      return $row['sepal-length'] == 59;
    });
    $this->assertEquals(null, $filtered);
  }

  public function testIndexes()
  {
    [$df] = $this->_loadFrame();
    $this->assertSame([0, 1, 2], $df->indexes());
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

    $this->assertEquals([5.1, 12.1, 18.4], math::nf_round($df->cumsum('sepal-length'), 1));

    $exp = [
      [5.1, 3.5, 1.4, 0.2, 'Iris-setosa'],
      [12.1, 6.7, 6.1, 1.6, 'Iris-versicolor'],
      [18.4, 10, 12.1, 4.1, 'Iris-virginica']
    ];
    foreach ($df->cumsum()->round(1) as $i => $row) {
      $this->assertEquals($exp[$i], array_values($row));
    }
  }

  public function testCumulativeProduct()
  {
    [$df] = $this->_loadFrame();
    $df->round(2, inPlace: true);

    $this->assertEquals([5.10, 35.70, 224.91], math::nf_round($df->cumproduct('sepal-length'), 2));

    $exp = [
      [5.10, 3.50, 1.40, 0.20, 'Iris-setosa'],
      ['35.70', 11.20, 6.58, 0.28, 'Iris-versicolor'],
      ['224.91', 36.96, 39.48, 0.70, 'Iris-virginica']
    ];
    foreach ($df->cumproduct()->round(2) as $i => $row) {
      $this->assertEquals($exp[$i], array_values($row));
    }
  }

  public function testCumulativeMax()
  {
    [$df] = $this->_loadFrame();
    $df->round(1, inPlace: true);

    $this->assertEquals([5.1, 7.0, 7.0], math::nf_round($df->cummax('sepal-length'), 1));

    $exp = [
      [5.1, 3.5, 1.4, 0.2, 'Iris-setosa'],
      [7.0, 3.5, 4.7, 1.4, 'Iris-versicolor'],
      [7.0, 3.5, 6.0, 2.5, 'Iris-virginica']
    ];
    foreach ($df->cummax()->round(1) as $i => $row) {
      $this->assertEquals($exp[$i], array_values($row));
    }
  }

  public function testCumulativeMin()
  {
    [$df] = $this->_loadFrame();
    $df->round(1, inPlace: true);

    $this->assertEquals([5.1, 5.1, 5.1], math::nf_round($df->cummin('sepal-length'), 1));

    $exp = [
      [5.1, 3.5, 1.4, 0.2, 'Iris-setosa'],
      [5.1, 3.2, 1.4, 0.2, 'Iris-versicolor'],
      [5.1, 3.2, 1.4, 0.2, 'Iris-virginica']
    ];
    foreach ($df->cummin()->round(1) as $i => $row) {
      $this->assertEquals($exp[$i], array_values($row));
    }
  }


  public function testGroupby()
  {
    $original = [
      ['name' => 'Falcon', 'Animal' => 'bird', 'Age' => 8, 'size' => 'big'],
      ['name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
      ['name' => 'Goat', 'Animal' => 'mammal', 'Age' => 12, 'size' => 'small'],
      ['name' => 'Possum', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big']
    ];
    $df = dataframe($original);
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

    $this->assertSame($original, $g->combine()->data());
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

  public function testTransformer()
  {
    [$df] = $this->_loadFrame();

    $df->apply_display_transformer(function ($v) {
      return floor((float)$v);
    }, 'sepal-length', 'sepal-width');

    $exp = [
      [5.0, 3.0, 1.4, 0.2, 'Iris-setosa'],
      [7.0, 3.0, 4.7, 1.4, 'Iris-versicolor'],
      [6.0, 3.0, 6.0, 2.5, 'Iris-virginica']
    ];
    [$transformed] = $df->report_data();
    foreach ($transformed as $i => $row) {
      $this->assertEquals($exp[$i], array_values($row));
    }
  }

  public function testFrequency()
  {
    $df = dataframe([
      ['name' => 'Falcon', 'Animal' => 'bird', 'Age' => 8, 'size' => 'big'],
      ['name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
      ['name' => 'Goat', 'Animal' => 'mammal', 'Age' => 12, 'size' => 'small'],
      ['name' => 'Possum', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big']
    ]);

    $exp = [2, 2];

    $this->assertEquals($exp, $df->frequency('Animal')->values('Frequency'));
  }

  public function testGaps()
  {
    $dataset = dataframe([
      ['recorded' => '2020-04-10 14:00', 'name' => 'Falcon', 'Animal' => 'bird', 'Age' => 8, 'size' => 'big'],
      ['recorded' => '2020-04-10 14:05', 'name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
      ['recorded' => '2020-04-10 14:10', 'name' => 'Goat', 'Animal' => 'mammal', 'Age' => 12, 'size' => 'small'],
      ['recorded' => '2020-04-10 14:20', 'name' => 'Possum', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big'],
      ['recorded' => '2020-04-10 14:26', 'name' => 'Snail', 'Animal' => 'insect', 'Age' => 0.3, 'size' => 'small'],
      ['recorded' => '2020-04-10 14:30', 'name' => 'Ant', 'Animal' => 'insect', 'Age' => 0.1, 'size' => 'small'],
      ['recorded' => '2020-04-10 14:45', 'name' => 'Cow', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big'],
      ['recorded' => '2020-04-10 14:50', 'name' => 'Sheep', 'Animal' => 'mammal', 'Age' => 1, 'size' => 'big']
    ])
      ->transform(function ($v) {
        return strtotime($v);
      }, 'recorded');

    $gaps = $dataset->gaps(5 * 60, 'recorded')
      ->apply_display_transformer(function ($v) {
        return date('d/m/Y h:i a', $v);
      }, 'start', 'end');

    $this->assertEquals(2, count($gaps));

    $exp = [
      ['start' => '10/04/2020 02:10 pm', 'end' => '10/04/2020 02:20 pm', 'segments' => 1],
      ['start' => '10/04/2020 02:30 pm', 'end' => '10/04/2020 02:45 pm', 'segments' => 2]
    ];
    [$rdata] = $gaps->report_data();
    $this->assertEquals($exp, $rdata);


    // test no gaps.
    $dataset = dataframe([
      ['recorded' => '2020-04-10 14:00', 'name' => 'Falcon', 'Animal' => 'bird', 'Age' => 8, 'size' => 'big'],
      ['recorded' => '2020-04-10 14:05', 'name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
      ['recorded' => '2020-04-10 14:10', 'name' => 'Goat', 'Animal' => 'mammal', 'Age' => 12, 'size' => 'small'],
      ['recorded' => '2020-04-10 14:15', 'name' => 'Possum', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big'],
      ['recorded' => '2020-04-10 14:20', 'name' => 'Snail', 'Animal' => 'insect', 'Age' => 0.3, 'size' => 'small'],
      ['recorded' => '2020-04-10 14:25', 'name' => 'Ant', 'Animal' => 'insect', 'Age' => 0.1, 'size' => 'small'],
      ['recorded' => '2020-04-10 14:30', 'name' => 'Cow', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big'],
      ['recorded' => '2020-04-10 14:35', 'name' => 'Sheep', 'Animal' => 'mammal', 'Age' => 1, 'size' => 'big']
    ])
      ->transform(function ($v) {
        return strtotime($v);
      }, 'recorded');

    $gaps = $dataset->gaps(5 * 60, 'recorded');
    $this->assertSame(null, $gaps);
  }

  public function testDuplicates()
  {
    $dataset = dataframe([
      ['recorded' => '2019-08-20', 'name' => 'Falcon', 'Animal' => 'bird', 'Age' => 8, 'size' => 'big'],
      ['recorded' => '2020-01-08', 'name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
      ['recorded' => '2019-06-27', 'name' => 'Goat', 'Animal' => 'mammal', 'Age' => 12, 'size' => 'small'],
      ['recorded' => '2018-05-01', 'name' => 'Possum', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big'],
      ['recorded' => '2020-01-08', 'name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
    ]);
    $dups = $dataset->duplicated();

    $this->assertSame([[1, 4]], $dups);
  }

  public function testDropDuplicates()
  {
    $dataset = dataframe([
      ['recorded' => '2019-08-20', 'name' => 'Falcon', 'Animal' => 'bird', 'Age' => 8, 'size' => 'big'],
      ['recorded' => '2020-01-08', 'name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
      ['recorded' => '2019-06-27', 'name' => 'Goat', 'Animal' => 'mammal', 'Age' => 12, 'size' => 'small'],
      ['recorded' => '2018-05-01', 'name' => 'Possum', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big'],
      ['recorded' => '2020-01-08', 'name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
    ]);
    $dropped = $dataset->drop_duplicates();

    $exp = [
      ['2019-08-20', 'Falcon', 'bird', 8, 'big'],
      ['2020-01-08', 'Pigeon', 'bird', 4, 'small'],
      ['2019-06-27', 'Goat', 'mammal', 12, 'small'],
      ['2018-05-01', 'Possum', 'mammal', 2, 'big']
    ];
    foreach ($dropped as $i => $row) {
      $this->assertSame($exp[$i], array_values($row));
    }
  }

  public function testOob()
  {
    $data = [
      ['amps' => 0.3],
      ['amps' => 0.5],
      ['amps' => 2.1],
      ['amps' => 1.1],
      ['amps' => 1.3],
      ['amps' => 1.15],
      ['amps' => 0.95]
    ];
    $df = dataframe($data);

    // column
    $oob = $df->oob(0.8, 1.8, 'amps');
    $exp = [
      ['lower' => 0.3, 'upper' => null],
      ['lower' => 0.5, 'upper' => null],
      ['lower' => null, 'upper' => 2.1]
    ];
    foreach ($oob as $i => $row) {
      $this->assertEquals($exp[$i]['lower'], $row['lower']);
      $this->assertEquals($exp[$i]['upper'], $row['upper']);
    }

    // all columns
    $oob = $df->oob(0.8, 1.8);
    $exp = [
      ['index' => 0, 'lower' => 0.3, 'upper' => null],
      ['index' => 1, 'lower' => 0.5, 'upper' => null],
      ['index' => 2, 'lower' => null, 'upper' => 2.1]
    ];
    foreach ($oob as $i => $row) {
      $this->assertEquals($exp[$i]['index'], $row['index']);
      $this->assertEquals($exp[$i]['lower'], $row['lower']);
      $this->assertEquals($exp[$i]['upper'], $row['upper']);
    }

    $oob = $df->oob(-1, 99);
    $this->assertEquals(null, $oob);
  }

  public function testOobRegions()
  {
    $data = [
      ['amps' => 0.3],
      ['amps' => 0.5],
      ['amps' => 0.9],
      ['amps' => 1.23],
      ['amps' => 1.25],
      ['amps' => 1.55],
      ['amps' => 1.67],
      ['amps' => 1.77],
      ['amps' => 1.62],
      ['amps' => 1.45],
      ['amps' => 1.69],
      ['amps' => 1.58],
      ['amps' => 1.82],
      ['amps' => 1.87],
      ['amps' => 2.1],
      ['amps' => 2.2],
      ['amps' => 2.8],
      ['amps' => 1.9],
      ['amps' => 1.63],
      ['amps' => 1.1],
      ['amps' => 1.3],
      ['amps' => 1.15],
    ];
    $df = dataframe($data);

    $upper = $df->oob_region(1.8, OOB_UPPER, 'amps');
    $lower = $df->oob_region(0.8, OOB_LOWER, 'amps');

    $this->assertSame(1, $upper->count());
    $this->assertSame(1, $lower->count());

    $this->assertSame(12, $upper[0]['start']);
    $this->assertSame(17, $upper[0]['end']);
    $this->assertSame(0, $lower[0]['start']);
    $this->assertSame(1, $lower[0]['end']);

    $data = [
      ['amps' => 0.3],
      ['amps' => 0.1],
      ['amps' => 0.0],
      ['amps' => 0.0],
      ['amps' => -0.2],
      ['amps' => 0.0],
    ];

    $oob = dataframe($data)->oob_region(0.0, OOB_ALL, 'amps');
    $exp = [
      ['start' => 0, 'end' => 1],
      ['start' => 4, 'end' => 4]
    ];
    $this->assertSame(count($exp), count($oob));

    foreach ($oob as $i => $row) {
      $this->assertSame($exp[$i], $row);
    }

    $oob = dataframe($data)->oob_region(999, OOB_UPPER, 'amps');
    $this->assertEquals(null, $oob);
  }

  public function testTransform()
  {
    $df = dataframe([
      ['n' => 1],
      ['n' => 2],
      ['n' => 3]
    ]);

    $df = $df->transform(function ($v) {
      return $v * 10;
    }, 'n');

    $exp = [10, 20, 30];
    foreach ($df as $i => $row) {
      $this->assertSame($exp[$i], $row['n']);
    }
  }

  public function testReplace()
  {
    $orig = [
      'Col 1' => range(1, 100),
      'Col 2' => range(200, 300)
    ];

    $df = dataframe($orig, null, true);
    $df->add_row(['Col 2' => 31]); // test empty row as well.

    $replacement = range(1000, 1101);
    $df->replace('Col 1', $replacement);
    $this->assertSame($replacement, $df->values('Col 1', false));

    $this->expectException(InvalidArgumentException::class);
    $df->replace('Col 3', $replacement);


    $this->expectException(LengthException::class);
    $df->replace('Col 1', range(2, 99));
  }

  public function testPivotDepivot()
  {
    $dataset = dataframe([
      ['name' => 'Falcon', 'Animal' => 'bird', 'Age' => 8, 'size' => 'big'],
      ['name' => 'Pigeon', 'Animal' => 'bird', 'Age' => 4, 'size' => 'small'],
      ['name' => 'Goat', 'Animal' => 'mammal', 'Age' => 12, 'size' => 'small'],
      ['name' => 'Possum', 'Animal' => 'mammal', 'Age' => 2, 'size' => 'big']
    ]);
    $p = $dataset->pivot('Animal', 'Age');
    $exp = [
      'Animal' => ['_index' => 0, '_value' => 'bird'],
      0 => ['_index' => 1, '_value' => 'bird'],
      1 => ['_index' => 2, '_value' => 'mammal'],
      2 => ['_index' => 3, '_value' => 'mammal'],
      'Age' => ['_index' => 0, '_value' => 8],
      3 => ['_index' => 1, '_value' => 4],
      4 => ['_index' => 2, '_value' => 12],
      5 => ['_index' => 3, '_value' => 2]
    ];
    $this->assertSame($exp, $p->data());

    $dp = $p->depivot('Age');
    $exp = [8, 4, 12, 2];
    $this->assertSame($exp, $dp->values('Age'));
    $this->assertSame(1, count($dp->headers()));
  }

  public function testReindexRowsWithColumn()
  {
    [$df] = $this->_loadFrame();

    // copy
    $re = $df->reindex_rows_with_column('class');
    $exp = ['Iris-setosa', 'Iris-versicolor', 'Iris-virginica'];
    $this->assertSame($exp, $re->indexes());
    $this->assertSame(['sepal-length', 'sepal-width', 'petal-length', 'petal-width'], $re->headers());

    // in place
    $df->reindex_rows_with_column('class', true);
    $this->assertSame($exp, $df->indexes());
    $this->assertSame(['sepal-length', 'sepal-width', 'petal-length', 'petal-width'], $df->headers());
  }

  public function testReindexRows()
  {
    [$df] = $this->_loadFrame();

    $newKeys = ['a', 'b', 'c'];
    $this->assertSame($newKeys, $df->reindex_rows($newKeys)->indexes());
    $this->assertSame($newKeys, $df->reindex_rows($newKeys, true)->indexes());

    $this->expectException(LengthException::class);
    $newKeys = [1, 2];
    $this->assertSame([1, 2, 'c'], $df->reindex_rows($newKeys)->indexes());
  }

  public function testChangeHeader()
  {
    [$df] = $this->_loadFrame();
    $re = $df->change_header('sepal-length', 'SLen');

    $changed = ['sepal-width', 'petal-length', 'petal-width', 'class', 'SLen'];
    $this->assertSame($changed, $re->headers());
    $this->assertSame($changed, $df->change_header('sepal-length', 'SLen', true)->headers());
  }

  public function testFlattened()
  {
    [$df] = $this->_loadFrame();
    $df->reindex_rows_with_column('class', true);

    $exp = [
      ['Iris-setosa', 5.1, 3.5],
      ['Iris-versicolor', 7.0, 3.2],
      ['Iris-virginica', 6.3, 3.3]
    ];
    $this->assertEquals($exp, $df->flattened(true, ['sepal-length', 'sepal-width']));
    $exp = [
      [5.1, 3.5],
      [7.0, 3.2],
      [6.3, 3.3]
    ];
    $this->assertEquals($exp, $df->flattened(false, ['sepal-length', 'sepal-width']));

    $exp = [
      ['Iris-setosa', 5.1, 3.5, 1.4, 0.2],
      ['Iris-versicolor', 7.0, 3.2, 4.7, 1.4],
      ['Iris-virginica', 6.3, 3.3, 6.0, 2.5]
    ];
    $this->assertEquals($exp, $df->flattened(true));
  }

  public function testNormalise()
  {
    $df = dataframe([
      ['t1' => 0, 't2' => 5],
      ['t1' => 5, 't2' => 10],
      ['t1' => 10, 't2' => 15],
      ['t1' => 15, 't2' => 20],
      ['t1' => 20, 't2' => 25]
    ]);
    $this->assertEquals([0, 0.25, 0.5, 0.75, 1], $df->normalise('t1'));

    $exp = dataframe([
      ['t1' => 0, 't2' => 0],
      ['t1' => 0.25, 't2' => 0.25],
      ['t1' => 0.5, 't2' => 0.5],
      ['t1' => 0.75, 't2' => 0.75],
      ['t1' => 1, 't2' => 1]
    ]);

    $this->assertEquals($exp->data(), $df->normalise('t1', 't2')->data());

    $this->expectException(LengthException::class);
    $df->normalise();
  }

  protected function pixels($img)
  {
    $width = imagesx($img);
    $height = imagesy($img);
    $pixels = [];

    foreach (sequence($height - 1) as $y) {
      foreach (sequence($width - 1) as $x) {
        $rgb = imagecolorat($img, $x, $y);
        $colours = imagecolorsforindex($img, $rgb);
        $pixels[$y][$x] = $colours;
      }
    }
    return $pixels;
  }

  protected function gdAvailable()
  {
    $exists = function_exists('imagecreatefromstring') && function_exists('imagecreatefrompng');
    if (! $exists) {
      error_log("### GD not available, unable to perform charting tests.");
    }
    return $exists;
  }

  protected function compareImages(string $rendered, string $example)
  {
    $rendered = imagecreatefromstring($rendered);
    $example = imagecreatefrompng(__DIR__ . "/plots/$example.png");

    $rpixels = $this->pixels($rendered);
    $epixels = $this->pixels($example);

    foreach (sequence(0, 299) as $y) {
      foreach (sequence(0, 399) as $x) {
        $this->assertEquals($epixels[$y][$x], $rpixels[$y][$x], "Coords: (x:$x,y:$y)");
      }
    }
  }

  public function testBoxPlot()
  {
    if (! $this->gdAvailable()) {
      return;
    }

    foreach (boxPlot() as $i => $img) {
      $this->compareImages($img, "box_$i");
    }
  }

  public function testStockPlot()
  {
    if (! $this->gdAvailable()) {
      return;
    }

    $img = stockPlot();

    $this->compareImages($img, "candlesticks");
  }

  public function testHistogram()
  {
    if (! $this->gdAvailable()) {
      return;
    }

    foreach (histogram() as $i => $img) {
      $this->compareImages($img, "hist_$i");
    }
  }

  public function testHistogramWithBins()
  {
    if (! $this->gdAvailable()) {
      return;
    }

    foreach (histogramWithBins() as $i => $img) {
      $this->compareImages($img, "histbin_$i");
    }
  }

  public function testsCumulativeHistogram()
  {
    if (! $this->gdAvailable()) {
      return;
    }

    foreach (cumulativeHistogram() as $i => $img) {
      $this->compareImages($img, "histcum_$i");
    }
  }

  public function testPlot()
  {
    if (! $this->gdAvailable()) {
      return;
    }

    foreach (genericPlot() as $i => $img) {
      $this->compareImages($img, "gen_$i");
    }
  }

  # positioned right at the end because of the requirement of EMPTY_DATAFRAMES.
  public function testFastAddRows(): void
  {
    define('EMPTY_DATAFRAMES', true);
    $df = new DataFrame();
    $rows = [
      ['a' => 1, 'b' => 2, 'c' => 3],
      ['a' => 4, 'b' => 5, 'c' => 6]
    ];
    $df->fast_add_rows($rows);
    $this->assertSame(expected: $rows, actual: $df->data());

    $extra_rows = [
      ['a' => 9, 'b' => 99, 'c' => 123],
      ['a' => 42, 'b' => 54, 'c' => 64]
    ];
    $df = new DataFrame($rows);
    $df->fast_add_rows($extra_rows);
    $this->assertSame(expected: array_merge($rows, $extra_rows), actual: $df->data());
  }
}
