###### PHEXT > [DataKit](../README.md) > [API Reference](index.md) > DOMScraper
------
### DOMScraper
A class for automatically navigating and extracting information out of a DOMDocument.

It works by providing it the HTML/XML content and then calling traverse(). The method will sequentially transcend each element provided, eventually dispaching the nodes to your callback when the last item type in the element array has been reached.
#### Methods
[__construct](#__construct)
[dom](#dom)
[traverse](#traverse)

------
##### __construct
```php
public function __construct(string $contents) 
```
Create a new scraper using the provided text content. The contents should be either XML or HTML.


------
##### dom
```php
public function dom() 
```
Return the DOMDocument object.


------
##### traverse
```php
public function traverse(array $elements, callable $callback, $current = null) 
```
Traverse a hierarchal series of elements in the document, drilling down to the final set and providing them back to your program for processing.

Elements array should be in format of:

```
[   'type' => 'id|tag|class',
'name' => 'elementID or tag type',
(optional) 'item' => int (used to only work said index in resulting array).
]
```

e.g.

```
traverse([
['type' => 'id', 'name' => 'container'], # fetch DIV called container
['type' => 'tag', 'name' => 'table', 'item' => 0] # get the first table inside 'container'
['type' => 'tag' 'name' => 'tr'] # fetch all rows inside the first table.
]);
```

In this example the table rows found from the last definition in the elements array would be passed to your callback, which takes one parameter only.

**Returns:**  array [BOOL $pass, STRING $errorMessage].

returned $pass is `TRUE` if 1 or more items were found and passed to the callback, `FALSE` otherwise.


------
