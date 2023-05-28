---
id: domops
title: 🪄 DOMOPS API
sidebar_label: DOMOPS
---
The DOMOPS API (Document Object Model Operations) provides a set of powerful methods that allow you to manipulate the behavior and content of the frontend of your application. These methods can be utilized both in the backend and directly within the frontend code.

These methods offer a robust toolkit for dynamic manipulation of the DOM elements and content, enhancing the interactive experience of your application's frontend.

## Methods

### **setContent**
- This method sets the content of a specified element.

    ```php
    <?php
    $response = new \dobron\BigPipe\DialogResponse();

    $response->setContent(
        'span.current-date', // selector
        date('Y-m-d H:i:s')
    );
  
    $response->send();
    ```

    ```javascript
    import DOM from "bigpipe-util/src/core/DOM";

    DOM.prependContent(
        document.querySelector('span.current-date'), // element
        new Date().toLocaleString()
    );
    ```
### **appendContent**
- Use this method to insert content as the last child of a specified element.

    ```php
    $response->appendContent(
        'ul.logs',
        '<li>appended line</li>'
    );
    ```

    ```javascript
    DOM.appendContent(
        document.querySelector('ul.logs'),
        '<li>appended line</li>'
    );
    ```

### **prependContent**
- This method inserts content as the first child of a specified element.

    ```php
    $response->prependContent(
        'ul.logs',
        '<li>prepended line</li>'
    );
    ```

    ```javascript
    DOM.prependContent(
        document.querySelector('ul.logs'),
        '<li>prepended line</li>'
    );
    ```

### **insertAfter**
- Use this method to insert content after a specified element.

    ```php
    $response->insertAfter(
        'div.card',
        '<div class="card">content</div>'
    );
    ```

    ```javascript
    DOM.insertAfter(
        document.querySelector('div.card'),
        '<div class="card">content</div>'
    );
    ```

### **insertBefore**
- This method inserts content before a specified element.

    ```php
    $response->insertBefore(
        'div.card',
        '<div class="card">content</div>'
    );
    ```

    ```javascript
    DOM.insertAfter(
        document.querySelector('div.card'),
        '<div class="card">content</div>'
    );
    ```

### **remove**
- This method inserts content before a specified element.

    ```php
    $response->remove(
        '#content[post-id="123"]',
    );
    ```

    ```javascript
    DOM.remove(
        document.querySelector('#content[post-id="123"]')
    );
    ```

### **replace**
- This method replaces a specified element with new content.

    ```php
    $response->replace(
        'div.content',
        '<div class="content">new content</div>',
    );
    ```

    ```javascript
    DOM.replace(
        document.querySelector('div.content'),
        '<div class="content">new content</div>'
    );
    ```

### **eval**
- Use this method to evaluate JavaScript code provided as a string.

    ```php
    $response->eval(
        'body',
        'alert("Hello World!");',
    );
    ```

    ```javascript
    DOM.eval(
        document.querySelector('body'),
        'alert("Hello World!");'
    );
    ```

## Live Example
You can observe this API in action in the [demo page](http://bigpipe.xf.cz/tutorial/basic-example) provided.
