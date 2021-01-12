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
use sqonk\phext\datakit\DOMScraper;

class DOMScraperTest extends TestCase
{
    public function testDataExtraction()
    {
        $expected = [
            ['Doug', 'Egbert', 'Cleaner', '3pm-5pm', 'Wed,Fri'],
            ['Jane', 'Stewart', 'Manager', '9am-5pm', 'Mon - Fri'],
            ['Tim', 'Mollen', 'Assistant Manager', '9am-5pm', 'Mon - Fri'],
            ['Sophie', 'Alexander', 'Book Keeper', '10am-3pm', 'Mon,Wed,Fri']
        ];
        $results = [];
        $scraper = new DOMScraper(file_get_contents(__DIR__.'/../docs/people.html'));
        $r = $scraper->traverse([
        	['type' => 'id', 'name' => 'pageData'],
        	['type' => 'tag', 'name' => 'table', 'item' => 1],
        	['type' => 'tag', 'name' => 'tbody'],
        	['type' => 'tag', 'name' => 'tr']
        ], 
        function($tr) use (&$results) {
        	$tds = $tr->getElementsByTagName('td');
	
        	$firstName = $tds[0]->textContent;
        	$lastName = $tds[1]->textContent;
        	$role = $tds[2]->textContent;
        	$hours = $tds[3]->textContent;
        	$days = $tds[4]->textContent;
	
        	$results[] = [$firstName, $lastName, $role, $hours, $days];
        });
        
        $this->assertSame($expected, $results);
        $this->assertSame([true, ''], $r);
    }
    
    public function testYield()
    {
        $expected = [
            ['Doug', 'Egbert', 'Cleaner', '3pm-5pm', 'Wed,Fri'],
            ['Jane', 'Stewart', 'Manager', '9am-5pm', 'Mon - Fri'],
            ['Tim', 'Mollen', 'Assistant Manager', '9am-5pm', 'Mon - Fri'],
            ['Sophie', 'Alexander', 'Book Keeper', '10am-3pm', 'Mon,Wed,Fri']
        ];
        $results = [];
        $scraper = new DOMScraper(file_get_contents(__DIR__.'/../docs/people.html'));
        $elements = [
        	['type' => 'id', 'name' => 'pageData'],
        	['type' => 'tag', 'name' => 'table', 'item' => 1],
        	['type' => 'tag', 'name' => 'tbody'],
        	['type' => 'tag', 'name' => 'tr']
        ];
        $results = [];
        foreach ($scraper->yield($elements, $r) as $tr)
        {
        	$tds = $tr->getElementsByTagName('td');
	
        	$firstName = $tds[0]->textContent;
        	$lastName = $tds[1]->textContent;
        	$role = $tds[2]->textContent;
        	$hours = $tds[3]->textContent;
        	$days = $tds[4]->textContent;
	
        	$results[] = [$firstName, $lastName, $role, $hours, $days];
        }
        
        $this->assertSame($expected, $results);
        $this->assertSame([true, ''], $r);
    }
}