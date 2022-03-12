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
        
        $importer = new CSVImporter(input:$data, inputIsRawData:true, headersAreFirstRow:true);
        
        $this->assertSame(true, $importer->validate());
        $this->assertSame($expected, $importer->all_remaining());
        
        // rewind and test output is same as before.
        $importer->reset();
        $this->assertSame(true, $importer->validate());
        $this->assertSame($expected[0], $importer->next_row());
        $this->assertSame(1, $importer->row_index());
        
        $exp2 = $expected;
        array_shift($exp2);
        $this->assertSame($exp2, $importer->all_remaining());
        
        $importer = new CSVImporter(input:$dataNoHead, inputIsRawData:true, customHeaders:['Name', 'Age', 'Height']);
        $this->assertSame(true, $importer->validate());
        $this->assertSame($expected, $importer->all_remaining());

        $this->expectError(E_USER_NOTICE);
        $this->expectErrorMessage('Provided CSV data is empty.');
        $importer = new CSVImporter(input:'', inputIsRawData:true);
        $this->assertSame(false, $importer->validate());
    }
}