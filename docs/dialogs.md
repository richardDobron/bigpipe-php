---
id: dialogs
title: 🖥 Dialogs API
sidebar_label: Dialogs
---

The Dialogs API utilizes the _Modal Vanilla dependency_ to facilitate the display of dialogs. This dependency is functionally and visually compatible with the Bootstrap framework. This library has been modified to allow multiple dialogs to be displayed simultaneously.

## Methods

### **setController**
- This method sets the JavaScript controller (module) for the dialog, allowing you to register additional event listeners (such as show, shown, hide, hidden) or other logic related to the dialog.

    ```php
    $response->setController("require('ModalMonitor')");
    ```

### **setTitle**
- Use this method to set the title of a dialog.

    ```php
    $response->setTitle("dialog title");
    ```

### **setBody**
- This method sets the body content of a dialog.

    ```php
    $response->setBody("dialog body");
    ```

### **setFooter**
- Use this method to set the footer content of a dialog.

    ```php
    $response->setFooter('dialog footer');
    ```

### **setDialog**
- This method sets the entire content of a dialog, including title, body, and footer.

    ```php
    $response->setDialog('dialog content');
    ```

### **closeDialogs**
- This method closes all opened dialogs.

    ```php
    $response->closeDialogs();
    ```

### **closeDialog**
- Use this method to close the currently displayed dialog.

    ```php
    $response->closeDialog();
    ```

### **dialog**
- This method renders the defined dialog.

    ```php
    $response->dialog();
    ```

## Live Example
You can observe this API in action in the [demo page](http://bigpipe.xf.cz/tutorial/dialogs) provided.

## Example
If you want to trigger a dialog from the backend:
```php
<?php
// dialog.php
$response = new \dobron\BigPipe\DialogResponse();

$response->setTitle('Dialog title')
    ->setController("require('ModalLogger')")
    ->setBody('html <strong>content</strong>')
    ->setFooter('<button>close</button>')
    ->dialog();

$response->send();
```

To open this dialog from the frontend, you can use the following HTML code:
```html
<a href="#"
   ajaxify="/dialog.php"
   rel="dialog">Open Dialog</a>
```

For frontend-triggered dialog invocation:

```javascript
import Dialog from "bigpipe-util/src/core/Dialog";

(new Dialog()).showFromModel({
    controller: 'ModalLogger',
    backdrop: 'static',
    title: 'Dialog title',
    body: 'html <strong>content</strong>',
    footer: '<button>close</button>',
});
```

Here's an example of the implementation of a controller that can be attached to the dialog:
```javascript
export default class ModalLogger
{
    constructor(dialog) {
        console.log('dialog data:', dialog);

        dialog.on('show', (event) => {
            console.log('event: show', event)
        });

        dialog.on('shown', (event) => {
            console.log('event: shown', event)
        });

        dialog.on('hide', (event) => {
            console.log('event: hide', event)
        });

        dialog.on('hidden', (event) => {
            console.log('event: hidden', event)
        });
    }
}
```
