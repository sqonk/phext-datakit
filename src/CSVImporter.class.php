<?php
namespace sqonk\phext\datakit;

/**
*
* Data Kit
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


class CSVImporter
{
    protected bool $headersAreFirstRow = false;
    protected array $customHeaders = [];
    
    public function __construct(protected string $input, protected bool $inputIsRawData = false)
    {
        
    }
}