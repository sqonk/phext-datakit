<?php
namespace sqonk\phext\datakit;
/**
*
* Data Kit
* 
* @package		phpext
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

/**
 * A class for automatically navigating and extracting information
 * out of a DOMDocument.
 * 
 * It works by providing it the HTML/XML content and then calling traverse().
 * The method will sequentially transcend each element provided, eventually
 * dispaching the nodes to your callback when the last item type in the
 * element array has been reached.
 */

use sqonk\phext\core\arrays;


class DOMScraper
{
    protected $dom;
    
    /**
     * Create a new scraper using the provided text content. The contents
     * should be either XML or HTML.
     */
    public function __construct(string $contents)
    {
        $this->dom = new \DomDocument;
		try {
            @$this->dom->loadHTML($contents);
            @$this->dom->preserveWhiteSpace = false;
		}
        finally {
        	// do nothing.
        }
    }
    
    /**
     * Return the DOMDocument object.
     */
    public function dom()
    {
        return $this->dom;
    }
    
    /**
     * Traverse a hierarchal series of elements in the document, drilling down to the final set
     * and providing them back to your program for processing.
     * 
     * Elements array should be in format of:
     * 
     * ```
     * [   'type' => 'id|tag|class',
     * 'name' => 'elementID or tag type',
     * (optional) 'item' => int (used to only work said index in resulting array).
     * ]
     * ```
     * 
     * e.g.
     * 
     * ```
     * traverse([
     * ['type' => 'id', 'name' => 'container'], # fetch DIV called container
     * ['type' => 'tag', 'name' => 'table', 'item' => 0] # get the first table inside 'container'
     * ['type' => 'tag' 'name' => 'tr'] # fetch all rows inside the first table.
     * ]);
     * ```
     * 
     * In this example the table rows found from the last definition in the elements array would
     * be passed to your callback, which takes one parameter only.
     * 
     * @return array [BOOL $pass, STRING $errorMessage].
     * 
     * returned $pass is TRUE if 1 or more items were found and passed to the callback, FALSE otherwise.
     */
    public function traverse(array $elements, callable $callback, $current = null)
    {
        if (count($elements) == 0)
            throw new \InvalidArgumentException("elements array can not be empty");
        
        if (! $current)
            $current = $this->dom;
        
        $info = array_shift($elements);
        $type = $info['type'];
        $key = $info['name'];
        
        $items = null;
        if ($type == 'id')
        {
            $items = $current->getElementById($key);
            if ($items && ! is_array($items))
                $items = [$items];
        }
        else if ($type == 'tag')
        {
            $item = $info['item'] ?? null;
			$class = $info['class'] ?? null;
            $items = $current->getElementsByTagName($key);
            if ($items && $item !== null) 
                $items = [$items[$item]]; 
			
			else if ($items && $class) {
				$filtered = [];
				foreach ($items as $el) {
					if ($el->hasAttributes()) {
						foreach ($el->attributes as $attr)
						{
							if ($attr->nodeName == 'class') {
								if ($attr->nodeValue == $class)
									$filtered[] = $el;
								break;
							}
						}
					}
				}
				$items = $filtered;
			}
        }
        else {
            throw new \UnexpectedValueException("$type is not a valid type");
        }
        
        if (! $items) 
            return [false, "Failed to find $key ($type)"];
                
        $remaining = count($elements); 
        foreach ($items as $item) {
            if ($remaining > 0) {
                list($ok, $msg) = $this->traverse($elements, $callback, $item);
                if (! $ok)
                    return [$ok, $msg];
            }
            else {
                $callback($item);
            }
                
        }
        
        return [(count($items) > 0), ''];
    }
}