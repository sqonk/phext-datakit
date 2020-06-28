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


class PackedArray implements \ArrayAccess, \Countable, \Iterator
{
    protected $buffer;
    protected $_currentPosition;
    protected $size;
    protected $indexes;
    protected $lengths;
    
    const INT_SIZE = 4;
    
    protected $_iteratorIndex = 0;
    
	public function offsetSet($index, $value)
	{
		if ($index === null)
			$this->add($value);
		else
			$this->set($index, $value);
	}
	
	public function offsetGet($index)
	{
		return $this->get($index);
	}
	
	public function offsetExists($index)
	{
		return $index < $this->_count;
	}
	
	public function offsetUnset($index)
	{
		$this->remove($index);
	}

    public function __toString()
    {
        return sprintf("PackedArray(%d) %s...%s", $this->indexes->count(), $this->first(), $this->last());
    }
    
    public function rewind() {
        $this->_iteratorIndex = 0;
    }

    public function current() {
        return $this->get($this->_iteratorIndex);
    }

    public function key() {
        return $this->_iteratorIndex;
    }

    public function next() {
        ++$this->_iteratorIndex;
    }

    public function valid() {
        return $this->_iteratorIndex < $this->_count;
    }
    
    public function count()
    {
        return $this->_count;
    }
    
    public function __construct(array $startingArray = [])
    {
        $this->buffer = new \SplFileObject('php://memory', 'rw+');
        $this->indexes = new ByteArray('l');
        $this->lengths = new ByteArray('l');
        
        $this->_currentPosition = 0;
        $this->size = 0;
        
        foreach ($startingArray as $item)
            $this->add($item);
    }

    public function add($value)
    {
        if ($this->_currentPosition != $this->size) {
            $this->buffer->fseek(SEEK_END);
        }
            
        // write out the index of the new value.
        $this->indexes->add($this->size);
        
        // serialise the value if it is either an array or object.
        if (is_array($value) || is_object($value))
            $value = serialize($value);
        
        $len = strlen($value);
        $this->buffer->fwrite(pack('l', $len).$value);
        
        $this->size += $len + self::INT_SIZE;
        $this->_currentPosition = $this->size;
        $this->_count++;
    }
    
    public function insert(int $index, $newVal)
    {
        if ($index < $this->_count-1)
        {
            // serialise the value if it is either an array or object.
            if (is_array($newVal) || is_object($newVal))
                $value = serialize($newVal);
            
            $newLen = strlen($newVal);
            
            // move everything after the insertion point along by the length of the new value.
            $currentIdx = $this->_count-1;
            while ($currentIdx > $index)
            {
                // get the position from the index.
                $this->indexes->fseek($currentIdx * self::INT_SIZE);
                $pos = unpack('l', $this->indexes->fread(self::INT_SIZE))[1];
                println("pos = $pos");
                
                // get the length and value from the buffer.
                $this->buffer->fseek($pos);
                $len = (int)unpack('l', $this->buffer->fread(self::INT_SIZE))[1]; 
                $value = $this->buffer->fread($len);
                println("len = $len", $value);
                
                // write the length and value back, shifted along.
                $this->buffer->fseek($pos+$newLen); println('writing out to', $pos+$newLen);
                $this->buffer->fwrite(pack('l', $newLen).$value);
                
                // update the index.
                $this->indexes->fseek($currentIdx+1 * self::INT_SIZE);
                $this->indexes->fwrite(pack('l', $pos+$newLen));

                $currentIdx--;
            }
            
            // Insert the new value.
            $this->indexes->fseek($index * self::INT_SIZE);
            $pos = unpack('l', $this->indexes->fread(self::INT_SIZE))[1];
            
            $this->buffer->fseek($pos+$newLen);
            $this->buffer->fwrite(pack('l', $newLen).$newVal);
            
            $this->size += $newLen + self::INT_SIZE;
            $this->_currentPosition = $this->buffer->ftell();
            $this->_count++;
        }
        
        else
            $this->add($newVal);
    }
    
    public function get(int $index)
    {
        [$value] = $this->_get($index);
        
        /* FIXME: unserialize value once detected. */
        
        return $value;
    }
    
    protected function _get(int $index)
    {
        $this->indexes->fseek($index * self::INT_SIZE);
        $pos = unpack('l', $this->indexes->fread(self::INT_SIZE))[1];
        
        if ($this->_currentPosition != $pos)
            $this->buffer->fseek($pos);
        
        $len = (int)unpack('l', $this->buffer->fread(self::INT_SIZE))[1]; 
        
        $value = $this->buffer->fread($len);
        $this->_currentPosition = $pos + $len + self::INT_SIZE;
                
        return [$value, $len, $pos];
    }
    
    public function remove(int $index)
    {
        // shift to starting position, where the element we wish to remove is.
        $this->indexes->fseek($index * self::INT_SIZE);
        $start = unpack('l', $this->indexes->fread(self::INT_SIZE))[1];
        $this->buffer->fseek($start);
        
        if ($index < $this->_count-1)
        {
            /*
                If we're removing an element somewhere before the end then
                back-shift everything that comes after.
            */
            $lastPos = $start;
            $len = (int)unpack('l', $this->buffer->fread(self::INT_SIZE))[1]; 
            
            foreach (sequence($index+1, $this->_count-1) as $i)
            {
                $this->buffer->fseek($len, SEEK_CUR);

                $lenBytes = $this->buffer->fread(self::INT_SIZE); 
                $len = (int)unpack('l', $lenBytes)[1]; 
                $segment = $this->buffer->fread($len);
                $this->buffer->fseek($lastPos);
                $this->buffer->fwrite($lenBytes.$segment);
                
                $lastPos = $this->buffer->ftell();
                $len = (int)unpack('l', $this->buffer->fread(self::INT_SIZE))[1]; 
            }
            
            // fix the position so it's ready for the deleting the last block of memory.
            $start = $lastPos;
        }

        // Delete last segment
        $this->size = $start;
        $this->buffer->ftruncate($this->size);
        $this->buffer->fseek($start);
        
        $this->_currentPosition = $this->size;
        $this->_count--;
    }
    
    public function clear()
    {
        $this->indexes->ftruncate(0);
        $this->indexes->rewind();
        
        $this->buffer->ftruncate(0);
        $this->buffer->rewind();
        
        $this->_currentPosition = 0;
        $this->size = 0;
        $this->_count = 0;
    }
}