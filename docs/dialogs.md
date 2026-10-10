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

## Options and behaviors

The dialog behaves the same on every page, and each behavior can be changed per dialog. The setters can be called in any
order before `dialog()`:

```php
$response->setTitle('Delete the post?')
    ->setBody($form)
    ->setBackdrop('static')          // true, false, or 'static': a click on the backdrop doesn't close it
    ->setKeyboard(false)             // Esc doesn't close it
    ->setHideOnSuccess('form')       // close it when the request of a form inside succeeds
    ->setPosition(80)                // the top margin in pixels
    ->dialog();
```

| Setter                                    | Default | Description                                                                       |
|-------------------------------------------|---------|-----------------------------------------------------------------------------------|
| `setBackdrop(bool|string)`               | `true`  | `false` for no backdrop, `'static'` for one that doesn't close the dialog.         |
| `setKeyboard(bool)`                      | `true`  | Close the dialog with <kbd>Esc</kbd>.                                              |
| `setAnimate(bool)`                       | `false` | Animate showing and hiding.                                                        |
| `setTimeout(int)`                        |         | Show the dialog after this many milliseconds.                                      |
| `setAutoFocus(bool)`                     | `true`  | Move the focus into the dialog when it is shown.                                   |
| `setTrapFocus(bool)`                     | `true`  | Keep <kbd>Tab</kbd> inside of the dialog.                                          |
| `setRefocus(bool)`                       | `true`  | Give the focus back to the element that opened the dialog when it closes.          |
| `setCausalElement(string $elementId)`    |         | The element to give the focus back to, by its id. The focused element by default.   |
| `setHideOnTransition(bool)`              | `true`  | Close the dialog before a [page transition](page_transitions.md).                  |
| `setHideOnSuccess(bool|string $selector)` | `false` | Close the dialog when a request sent from inside of it succeeds, from any element or only those that match the selector. |
| `setPosition(?int $top, bool $centered, bool $ignoreTopInShortViewport)` | | Set the top margin: `$top` pixels, or else a third of the free height of the window (half when centered). Without arguments, the default margin. |

`setOption($name, $value)`, `setOptions($array)` and the array given to `dialog($options)` set any option of the
browser part by its name, e.g. `setOption('transition', 300)`; `dialog($options)` wins over the setters. The styling
is up to your CSS: the markup is that of Bootstrap modals, and the options above add no classes.

The dialog is also a layer in the browser, whose `beforehide` event a controller can use to keep it open, see
[Layer](https://github.com/richardDobron/bigpipe-util/blob/main/docs/dialog.md#layer-behaviors).

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
import Dialog from "bigpipe-util/dist/core/Dialog";

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
