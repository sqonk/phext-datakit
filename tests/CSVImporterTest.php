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
use sqonk\phext\datakit\CSVImporter;

class CSVImporterTest extends TestCase
{
  protected function inMemoryDataSet(): array
  {
    $expected = [
        ['Name' => 'Doug', 'Age' => '23', 'Height' => '190'],
        ['Name' => 'Rob', 'Age' => '34', 'Height' => '186'],
        ['Name' => 'Jim', 'Age' => '40', 'Height' => '185'],
        ['Name' => 'Jose', 'Age' => '23', 'Height' => '189']
    ];
    $data = "Name,Age,Height\nDoug,23,190\nRob,34,186\nJim,40,185\nJose,23,189";
    $dataNoHead = "Doug,23,190\nRob,34,186\nJim,40,185\nJose,23,189";
        
    return [$expected, $data, $dataNoHead];
  }
    
  public function testHeadersAreFirstRow(): void
  {
    [$expected, $data] = $this->inMemoryDataSet();
    $importer = new CSVImporter(input:$data, inputIsRawData:true, headersAreFirstRow:true);
    $this->assertSame(['Name', 'Age', 'Height'], $importer->headers());
  }
    
  public function testNoHeaders(): void
  {
    [$expected, $data] = $this->inMemoryDataSet();
    $importer = new CSVImporter(input:$data, inputIsRawData:true);
    $this->assertSame(null, $importer->headers());
    $this->assertSame(true, $importer->validate());
  }
    
  public function testCustomHeaders(): void
  {
    [$expected, $data, $dataNoHead] = $this->inMemoryDataSet();
    $importer = new CSVImporter(input:$data, inputIsRawData:true, customHeaders:['A', 'B', 'C']);
    $this->assertSame(['A', 'B', 'C'], $importer->headers());
  }
    
  public function testOverrideHeadersWithCustom(): void
  {
    [$expected, $data] = $this->inMemoryDataSet();
    $importer = new CSVImporter(
      input:$data,
      inputIsRawData:true,
      headersAreFirstRow:true,
      customHeaders:['A', 'B', 'C']
    );
    $this->assertSame(['A', 'B', 'C'], $importer->headers());
  }
    
  public function testNextRowAndRowIndex(): void
  {
    [$expected, $data] = $this->inMemoryDataSet();
    $importer = new CSVImporter(input:$data, inputIsRawData:true, headersAreFirstRow:true);
    $this->assertSame(true, $importer->validate());
        
    $this->assertSame(1, $importer->row_index());
    $this->assertSame($expected[0], $importer->next_row());
    $this->assertSame(2, $importer->row_index());
    $this->assertSame($expected[1], $importer->next_row());
    $this->assertSame(3, $importer->row_index());
    $this->assertSame($expected[2], $importer->next_row());
    $this->assertSame(4, $importer->row_index());
    $this->assertSame($expected[3], $importer->next_row());
    $this->assertSame(5, $importer->row_index());
  }
    
  public function testAll(): void
  {
    [$expected, $data] = $this->inMemoryDataSet();
        
    $importer = new CSVImporter(input:$data, inputIsRawData:true, headersAreFirstRow:true);
    $this->assertSame(true, $importer->validate());
    $this->assertSame($expected, $importer->all());
    $this->assertSame($expected, $importer->all());
        
    $importer->reset();
    $importer->next_row();
    $this->assertSame($expected, $importer->all());
  }
    
  public function testAllRemaining(): void
  {
    [$expected, $data] = $this->inMemoryDataSet();
        
    $importer = new CSVImporter(input:$data, inputIsRawData:true, headersAreFirstRow:true);
    $this->assertSame(true, $importer->validate());
    $this->assertSame($expected, $importer->all_remaining());
    $this->assertSame([], $importer->all_remaining());
        
    $importer->reset();
    $importer->next_row();
    $exp2 = $expected;
    array_shift($exp2);
    $this->assertSame($exp2, $importer->all_remaining());
        
    $importer->reset();
    $this->assertSame($expected, $importer->all_remaining());
  }
    
  public function testSkipRows(): void
  {
    [$expected, $data] = $this->inMemoryDataSet();
    $importer = new CSVImporter(input:$data, inputIsRawData:true, headersAreFirstRow:true, skipRows:2);
        
    $exp2 = $expected;
    array_shift($exp2);
    array_shift($exp2);
    $this->assertSame($exp2, $importer->all_remaining());
        
    $expected = ['sepal-length' => '6.3', 'sepal-width' => '3.3', 'petal-length' => '6.0', 'petal-width' => '2.5', 'class' => 'Iris-virginica'];
    $importer = new CSVImporter(input:__DIR__.'/iris-h.csv', headersAreFirstRow:true, skipRows:2);
    $this->assertSame(true, $importer->valid());
    $this->assertSame([$expected], $importer->all());
  }
    
  public function testCustomDelimiter(): void
  {
    [$expected, $data] = $this->inMemoryDataSet();
    $data = str_replace(search:",", replace:"\t", subject:$data);
    $importer = new CSVImporter(input:$data, inputIsRawData:true, delimiter:"\t", headersAreFirstRow:true);
    $this->assertSame($expected, $importer->all());
  }
    
  public function testLineEnding(): void
  {
    [$expected, $data] = $this->inMemoryDataSet();
    $data = str_replace(search:"\n", replace:"\r", subject:$data);
    $importer = new CSVImporter(input:$data, inputIsRawData:true, lineEnding:"\r", headersAreFirstRow:true);
    $this->assertSame($expected, $importer->all());
  }
    
  public function testEnclosedBy(): void
  {
    $data = "'Name','Age','Height'\n'Doug','23','190'\n'Rob','34','186'\n'Jim','40','185'\n'Jose','23','189'";
    $importer = new CSVImporter(input:$data, inputIsRawData:true, headersAreFirstRow:true);
        
    $expected = [
        ['Name' => 'Doug', 'Age' => '23', 'Height' => '190'],
        ['Name' => 'Rob', 'Age' => '34', 'Height' => '186'],
        ['Name' => 'Jim', 'Age' => '40', 'Height' => '185'],
        ['Name' => 'Jose', 'Age' => '23', 'Height' => '189']
    ];
    $expectedEnc = [];
    foreach ($expected as $row) {
      $ro = [];
      foreach ($row as $k => $v) {
        $ro["'$k'"] = "'$v'";
      }
      $expectedEnc[] = $ro;
    }
    $this->assertSame($expectedEnc, $importer->all());
        
    $importer = new CSVImporter(input:$data, inputIsRawData:true, enclosedBy:"'", headersAreFirstRow:true);
    $this->assertSame($expected, $importer->all());
  }
    
  /**
   * By the time we get to doing a test for the actual import in in-memory data
   * there is very little to check as all the inner components have already been
   * tested.
   */
  public function testCsvFromData()
  {
    [$expected, $data, $dataNoHead] = $this->inMemoryDataSet();
        
    $importer = new CSVImporter(input:$data, inputIsRawData:true, headersAreFirstRow:true);
    $this->assertSame(true, $importer->validate());
  }
    
  public function testCsvFile()
  {
    $expected = [
        ['sepal-length' => '5.1', 'sepal-width' => '3.5', 'petal-length' => '1.4', 'petal-width' =>  '0.2', 'class' => 'Iris-setosa'],
        ['sepal-length' => '7.0', 'sepal-width' => '3.2', 'petal-length' => '4.7', 'petal-width' =>  '1.4', 'class' => 'Iris-versicolor'],
        ['sepal-length' => '6.3', 'sepal-width' => '3.3', 'petal-length' => '6.0', 'petal-width' => '2.5', 'class' => 'Iris-virginica']
    ];
    $importer = new CSVImporter(input:__DIR__.'/iris-h.csv', headersAreFirstRow:true);
    $this->assertSame(true, $importer->validate());
    $this->assertSame($expected, $importer->all());
        
        
    $headers = ['sepal-length','sepal-width','petal-length','petal-width','class'];
    $importer = new CSVImporter(input:__DIR__.'/iris-h.csv', headersAreFirstRow:true, customHeaders:$headers);
    $this->assertSame($expected, $importer->all());
        
    $this->expectException(RuntimeException::class);
    $importer = new CSVImporter(input:__DIR__.'/nofilehere.csv');
  }
    
  public function testSequentialRows(): void
  {
    [$expected, $data] = $this->inMemoryDataSet();
    $importer = new CSVImporter(input:$data, inputIsRawData:true, headersAreFirstRow:true);
        
    foreach ($expected as $erow) {
      $this->assertSame($erow, $importer->next_row());
    }
    $this->assertSame(count($expected), count($importer->all()));
  }
    
  public function testIterator(): void
  {
    [$expected, $data] = $this->inMemoryDataSet();
    $importer = new CSVImporter(input:$data, inputIsRawData:true, headersAreFirstRow:true);
        
    $i = 0;
    foreach ($importer as $row) {
      $this->assertSame($expected[$i], $row);
      $i++;
    }
  }
}
