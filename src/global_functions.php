<?php
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

/*
	Methods imported across the global namespace.
*/

define('ASCENDING', true);
define('DESCENDING', false);
define('OOB_ALL', 2);
define('OOB_UPPER', 1);
define('OOB_LOWER', 0);
define('LAST_ROW', '__LASTROW__');
define('FIRST_ROW', '__FIRSTROW__');

/**
 * Create a new vector.
 * 
 * This method takes a variable set of parameters, with each being added as
 * a seperate element within the array.
 * 
 * If only one element is passed in and it is an array then the array will
 * be transformed into the vector.
 */ 
function vector(...$items)
{
	if (count($items) == 1 and is_array($items[0]))
		$items = $items[0];
	return new \sqonk\phext\datakit\Vector($items);
}

/**
 * Create a new DataFrame with the supplied rows & columns.
 * 
 * @see DataFrame::__construct() for a proper description.
 */
function dataframe(?array $data = null, array $headers = null)
{
	return new \sqonk\phext\datakit\DataFrame($data, $headers);
}