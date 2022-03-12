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
use sqonk\phext\datakit\CSVExporter;

class CSVExporterTest extends TestCase
{
    public function testHeaders()
    {
        $h = ['Name', 'Age', 'Num'];
        $csv = new CSVExporter;
        $this->assertSame(true, $csv->set_headers($h));
        $this->assertSame($h, $csv->headers());
        
        $csv->add_raw_row(['Doug', 32, 20]);
        $this->expectError(E_USER_WARNING);
        $this->assertSame(false, $csv->set_headers($h));
    }
    
    public function testMap()
    {
        $m = ['Name' => 'a', 'Age' => 'b', 'Num' => 'c'];
        $csv = new CSVExporter;
        $this->assertSame(true, $csv->set_map($m));
        $this->assertSame($m, $csv->map());
        $this->assertSame(array_keys($m), $csv->headers());
        
        $csv->add_record(vector(['a' => 'Doug', 'b' => 32, 'c' => 20]));
        $this->expectError(E_USER_WARNING);
        $this->assertSame(false, $csv->set_map($m));
    }
    
    public function testAddMapPair()
    {
        $csv = new CSVExporter;
        $this->assertSame(true, $csv->add_map_pair('Name', 'a'));
        $this->assertSame(['Name' => 'a'], $csv->map());
        
        $csv->add_record(vector(['a' => 'Doug', 'b' => 32, 'c' => 20]));
        $this->expectError(E_USER_WARNING);
        $this->assertSame(false, $csv->add_map_pair('Age', 'b'));
    }
    
    public function testAddRawRow()
    {
        $csv = new CSVExporter;
        $csv->set_headers(['Name', 'Age', 'Num']);
        $csv->add_raw_row(['Doug', 32, 20]);
        
        $expected = "Name,Age,Num\nDoug,32,20";
        $this->assertSame($expected, (string)$csv);
    }
    
    public function testAddRecord()
    {
        $csv = new CSVExporter;
        $csv->set_map(['Name' => 'a', 'Age' => 'b', 'Num' => 'c']);
        $csv->add_record(vector(['a' => 'Doug', 'b' => 32, 'c' => 20]));
        $csv->add_record(['a' => 'Jane', 'b' => 25, 'c' => 21]);
        
        $expected = "Name,Age,Num\nDoug,32,20\nJane,25,21";
        $this->assertSame($expected, (string)$csv);
    }
    
    public function testAddRecords()
    {
        $csv = new CSVExporter;
        $csv->set_map(['Name' => 'a', 'Age' => 'b', 'Num' => 'c']);
        $csv->add_records([
            vector(['a' => 'Doug', 'b' => 32, 'c' => 20]),
            ['a' => 'Jane', 'b' => 25, 'c' => 21]
        ]);
        
        $expected = "Name,Age,Num\nDoug,32,20\nJane,25,21";
        $this->assertSame($expected, (string)$csv);
    }
}