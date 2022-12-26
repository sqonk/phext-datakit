###### PHEXT > [DataKit](../README.md) > [API Reference](index.md) > DOMScraper
------
### DOMScraper
A class for automatically navigating and extracting information out of a DOMDocument.

It works by providing it the HTML/XML content and then calling traverse(). The method will sequentially transcend each element provided, eventually dispatching the nodes to your callback when the last item type in the element array has been reached.
#### Methods
- [__construct](#__construct)
- [dom](#dom)
- [traverse](#traverse)
- [yield](#yield)

------
##### __construct
```php
public function __construct(string $contents) 
```
Create a new scraper using the provided text content. The contents should be either XML or HTML.


------
##### dom
```php
public function dom() : DomDocument
```
Return the DOMDocument object.


------
##### traverse
```php
public function traverse(array $elements, callable $callback, DOMNode $current = null) : array
```
Traverse a hierarchal series of elements in the document, drilling down to the final set and providing them back to your program for processing.

- **list<array<string,** string>> $elements The configuration array of elements to traverse (see below examples).
- **callable** $callback A callback method that will repeatably receive each element at the end of the traversal chain.
- **?DOMNode** $current The parent node to begin from. This parameter services the recursive nature of the method and should be left as `NULL`.

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
], ...$callback);
```

In this example the table rows found from the last definition in the elements array would be passed to your callback, which takes one parameter only.

**Returns:**  array{bool, string} A two-member array. The first element contains `TRUE` or `FALSE` on whether the traversal was successful. If `FALSE`, the second element contains the error message.

The first element of the result ($pass) is `TRUE` if 1 or more items were found and passed to the callback, `FALSE` otherwise.


------
##### yield
```php
public function yield(array $elements, array &$result = null) : Generator
```
This method is the same as `traverse` but operates as a Generator for use within a foreach loop. Unlike the `traverse` method however, `yield` will first gather all found elements in memory and then distribute them one at a time to your foreach loop.

- **list<array<string,** string>> $elements The configuration array of elements to traverse (see below examples).
- **?array{bool,** string} &$result If passed in, sets the value to the result of the `traverse` method that was called internally.


------
