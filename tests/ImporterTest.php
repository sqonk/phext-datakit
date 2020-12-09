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
use sqonk\phext\datakit\Importer;

class ImporterTest extends TestCase
{
    public function testCsvFromData()
    {
        $expected = [
            ['Name' => 'Doug', 'Age' => '23', 'Height' => '190'],
            ['Name' => 'Rob', 'Age' => '34', 'Height' => '186'],
            ['Name' => 'Jim', 'Age' => '40', 'Height' => '185'],
            ['Name' => 'Jose', 'Age' => '23', 'Height' => '189']
        ];
        $data = "Name,Age,Height\nDoug,23,190\nRob,34,186\nJim,40,185\nJose,23,189";
        $dataNoHead = "Doug,23,190\nRob,34,186\nJim,40,185\nJose,23,189";
        
        $loaded = Importer::csv_data(null, $data, true);
        $this->assertSame($expected, $loaded);
        
        $loaded = [];
        Importer::csv_data(function($row) use (&$loaded) {
            $loaded[] = $row;
        }, $data, true);
        $this->assertSame($expected, $loaded);
        
        $loaded = Importer::csv_data(null, $dataNoHead, false, ['Name', 'Age', 'Height']);
        $this->assertSame($expected, $loaded);
        
        $this->expectError(E_USER_NOTICE);
        $this->assertSame(false, Importer::csv_data(null, '', true));
    }
    
    public function testCsvFile()
    {
        $expected = [
            ['sepal-length' => '5.1', 'sepal-width' => '3.5', 'petal-length' => '1.4', 'petal-width' =>  '0.2', 'class' => 'Iris-setosa'],
            ['sepal-length' => '7.0', 'sepal-width' => '3.2', 'petal-length' => '4.7', 'petal-width' =>  '1.4', 'class' => 'Iris-versicolor'],
            ['sepal-length' => '6.3', 'sepal-width' => '3.3', 'petal-length' => '6.0', 'petal-width' => '2.5', 'class' => 'Iris-virginica']
        ];
        $loaded = Importer::csv_file(null, __DIR__.'/iris-h.csv', true);
        $this->assertSame($expected, $loaded);
        
        $loaded = [];
        Importer::csv_file(function($row) use (&$loaded) {
            $loaded[] = $row;
        }, __DIR__.'/iris-h.csv', true);
        $this->assertSame($expected, $loaded);
        
        $headers = ['sepal-length','sepal-width','petal-length','petal-width','class'];
        $loaded = Importer::csv_file(null, __DIR__.'/iris-h.csv', false, $headers, 1);
        $this->assertSame($expected, $loaded);
        
        $this->expectException(RuntimeException::class);
        Importer::csv_file(null, __DIR__.'/nofilehere.csv', true);
    }
    
    public function testYieldCsv()
    {
        $expected = [
            ['sepal-length' => '5.1', 'sepal-width' => '3.5', 'petal-length' => '1.4', 'petal-width' =>  '0.2', 'class' => 'Iris-setosa'],
            ['sepal-length' => '7.0', 'sepal-width' => '3.2', 'petal-length' => '4.7', 'petal-width' =>  '1.4', 'class' => 'Iris-versicolor'],
            ['sepal-length' => '6.3', 'sepal-width' => '3.3', 'petal-length' => '6.0', 'petal-width' => '2.5', 'class' => 'Iris-virginica']
        ];
        
        $loaded = [];
        foreach (Importer::yield_csv(__DIR__.'/iris-h.csv', true) as $row)
            $loaded[] = $row;
        $this->assertSame($expected, $loaded);
        
        $loaded = [];
        $headers = ['sepal-length','sepal-width','petal-length','petal-width','class'];
        foreach (Importer::yield_csv(__DIR__.'/iris-h.csv', false, $headers, 1) as $row)
            $loaded[] = $row;
        $this->assertSame($expected, $loaded);
        
        $this->expectException(RuntimeException::class);
        foreach (Importer::yield_csv(__DIR__.'/nofilehere.csv', false, $headers, 1) as $row);
    }
    
    public function testCsvDataframe()
    {
        $expected = [
            ['sepal-length' => '5.1', 'sepal-width' => '3.5', 'petal-length' => '1.4', 'petal-width' =>  '0.2', 'class' => 'Iris-setosa'],
            ['sepal-length' => '7.0', 'sepal-width' => '3.2', 'petal-length' => '4.7', 'petal-width' =>  '1.4', 'class' => 'Iris-versicolor'],
            ['sepal-length' => '6.3', 'sepal-width' => '3.3', 'petal-length' => '6.0', 'petal-width' => '2.5', 'class' => 'Iris-virginica']
        ];
        $headers = ['sepal-length','sepal-width','petal-length','petal-width','class'];
        
        $df = Importer::csv_dataframe(__DIR__.'/iris-h.csv', true);
        $this->assertSame($expected, $df->data());
        
        $df = Importer::csv_dataframe(__DIR__.'/iris-h.csv', $headers, 1);
        $this->assertSame($expected, $df->data());
        
        $this->expectException(RuntimeException::class);
        Importer::csv_dataframe(__DIR__.'/nofilehere.csv', true);
    }
    
    public function testDelimiteredData()
    {
        $data = "Name,Age,Height\nDoug,23,190\nRob,34,186\nJim,40,185\nJose,23,189";
        $dataNoHead = "Doug,23,190\nRob,34,186\nJim,40,185\nJose,23,189";
        $expected = [
            ['Name' => 'Doug', 'Age' => '23', 'Height' => '190'],
            ['Name' => 'Rob', 'Age' => '34', 'Height' => '186'],
            ['Name' => 'Jim', 'Age' => '40', 'Height' => '185'],
            ['Name' => 'Jose', 'Age' => '23', 'Height' => '189']
        ];
        $loaded = [];
        Importer::delimitered_data(function($row) use (&$loaded) {
            $loaded[] = $row;
        }, $data, ',', "\n", true);
        $this->assertSame($expected, $loaded);
        
        $loaded = [];
        $headers = ['Name', 'Age', 'Height'];
        Importer::delimitered_data(function($row) use (&$loaded) {
            $loaded[] = $row;
        }, $dataNoHead, ',', "\n", false, $headers);
        $this->assertSame($expected, $loaded);
        
        $this->expectError(E_USER_WARNING);
        $this->assertSame(false, Importer::delimitered_data(function($row) {}, '', ','));
    }
}