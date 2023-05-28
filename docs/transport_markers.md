---
id: transport_markers
title: 🧰 Transport Markers API
sidebar_label: Transport Markers
---

The Transport Markers enables you to send HTML content while also facilitating the transformation of content into JavaScript objects, such as Maps, Sets, or Elements. These markers serve as parameter definitions for frontend-side module calls.

## Methods

### **transportHtml**
- This method allows you to send HTML (or text) content using the `__html` marker.

    ```php
    $content = dobron\BigPipe\TransportMarker::transportHtml('new content');
    ```

### **transportModule**
- Transport a module via the `__m` marker.

    ```php
    $module = dobron\BigPipe\TransportMarker::transportModule('ChartRenderer');
    ```

### **transportElement**
- If you provide an element ID, the `__e` marker represents the element with a matching id property.

    ```php
    $element = dobron\BigPipe\TransportMarker::transportElement('chart-div');
    ```

### **transportMap**
- The `__map` marker creates a Map object that stores key-value pairs while preserving their original insertion order.

    ```php
    $itemsMap = dobron\BigPipe\TransportMarker::transportMap([
        ['Jack', 20],
        ['Alan', 34],
        ['Bill', 10],
        ['Sam', 9]
    ]);
    ```

### **transportSet**
- The `__set` marker generates a Set object that stores unique values.

    ```php
    $itemsSet = dobron\BigPipe\TransportMarker::transportSet([
        ['x' => 10, 'y' => 71],
        ['x' => 20, 'y' => 55],
        ['x' => 30, 'y' => 50],
        ['x' => 40, 'y' => 65],
    ]);
    ```

## Live Example
You can observe this API in action in the [demo page](http://bigpipe.xf.cz/tutorial/basic-example) provided.

## Example

```php
<?php
$response = new \dobron\BigPipe\AsyncResponse();

$response
    ->bigPipe()->require("require('tutorial/Collections').setup()", [
        TransportMarker::transportElement('data-box'),
        TransportMarker::transportMap([
            ['Jack', 20],
            ['Alan', 34],
            ['Bill', 10],
            ['Sam', 9]
        ]),
        $response->transport()->transportSet([
            'a', 'b',
            'c', 'c', 'c',
        ]),
    ]);

$response->send();
```
