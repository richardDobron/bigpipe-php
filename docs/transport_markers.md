---
id: transport_markers
title: 🧰 Transport Markers API
sidebar_label: Transport Markers
---

The Transport Markers enables you to send HTML content while also facilitating the transformation of content into JavaScript objects, such as Maps, Sets, or Elements. These markers serve as parameter definitions for frontend-side module calls.

## Methods

The methods are static. The older names, `transportHtml()`, `transportModule()`, `transportElement()`, `transportMap()`
and `transportSet()`, and `$response->transport()` still work, but are deprecated.

### **html**
- This method allows you to send HTML (or text) content using the `__html` marker. DOM operations insert it as HTML.
  A module gets it as it is, an object `{__html: '...'}`, so it decides how to insert it, e.g.
  `element.innerHTML = message.__html`.

    ```php
    $content = dobron\BigPipe\TransportMarker::html('new content');
    ```

### **module**
- Transport a module via the `__m` marker.

    ```php
    $module = dobron\BigPipe\TransportMarker::module('ChartRenderer');
    ```

### **element**
- If you provide an element ID, the `__e` marker represents the element with a matching id property.

    ```php
    $element = dobron\BigPipe\TransportMarker::element('chart-div');
    ```

### **map**
- The `__map` marker creates a Map object that stores key-value pairs while preserving their original insertion order.

    ```php
    $itemsMap = dobron\BigPipe\TransportMarker::map([
        ['Jack', 20],
        ['Alan', 34],
        ['Bill', 10],
        ['Sam', 9]
    ]);
    ```

### **set**
- The `__set` marker generates a Set object that stores unique values.

    ```php
    $itemsSet = dobron\BigPipe\TransportMarker::set([
        ['x' => 10, 'y' => 71],
        ['x' => 20, 'y' => 55],
        ['x' => 30, 'y' => 50],
        ['x' => 40, 'y' => 65],
    ]);
    ```

### **relative**
- The `__rel` marker stands for the element that sent the request, e.g. the link or the form of the Primer, or `null`
  for the modules of a page.

    ```php
    use dobron\BigPipe\TransportMarker;

    $response->call('Tooltip', 'show', [TransportMarker::relative(), 'Copied']);
    ```

## Live Example
You can observe this API in action in the [demo page](http://bigpipe.etexweb.sk/tutorial/transport-markers) provided.

## Example

```php
<?php
use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\TransportMarker;

$response = new AsyncResponse();

$response
    ->call('tutorial/Collections', 'setup', [
        TransportMarker::element('data-box'),
        TransportMarker::map([
            ['Jack', 20],
            ['Alan', 34],
            ['Bill', 10],
            ['Sam', 9]
        ]),
        TransportMarker::set([
            'a', 'b',
            'c', 'c', 'c',
        ]),
    ]);

$response->send();
```
