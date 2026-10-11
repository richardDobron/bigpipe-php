---
id: how_it_works
title: How it works
sidebar_label: How it works
---

The BigPipe integration works by utilizing the `dobron\BigPipe\AsyncResponse` class to process requests and generate the defined instructions. These instructions are then handled by the `ServerJS` class on the frontend. The required `Primer` module registers essential event listeners, such as click and submit, for elements with the `rel` attribute.


To make `XHR` (XMLHttpRequest) requests, you can use the `AsyncRequest` JavaScript class. It allows you to send requests to the server and receive responses, similar to using the native `fetch` method. These responses are automatically processed and executed through BigPipe. However, it's important to note that the response on the backend must always be sent using the `AsyncResponse` class.

## Usage example
Here's a step-by-step example of how to use BigPipe to display an alert with the name of the user who just logged in:

1. Create the file `UserLoggedInAlert.js`:

    ```javascript
    export default function UserLoggedInAlert(username) {
        alert(`Welcome, ${username}!`);
    }
    ```

2. Require the module on backend:

   In your PHP backend code, use the `dobron\BigPipe\AsyncResponse` class to require the module and pass arguments:

    ```php
    <?php
    $asyncResponse = new \dobron\BigPipe\AsyncResponse();
    
    $asyncResponse->call('UserLoggedInAlert', null, [
        'Marvin', // username argument
    ]);

    // If this request was made via AsyncRequest, call the send() method
    $asyncResponse->send();
    ```

   `send()` prints the response and ends the script. To let your framework or middleware finish the request, call
   `output()` instead: it prints the response the same way, but the script goes on. To build the response yourself,
   e.g. as a framework response object, use `buildResponseString()`.

## Instances
A call with a method creates a new object of a class every time. To keep one object and talk to it from more calls,
define an instance. The browser creates it as `new ChartRenderer(element, data)` the first time it is used and shares
it between all later uses, also in the following responses:

```php
<?php
$chart = $asyncResponse->bigPipe()->instance('ChartRenderer', [
    \dobron\BigPipe\TransportMarker::element('chart'),
    [10, 20, 30],
]);

$chart->call('render');
$chart->call('highlight', [2]);

// Passed as an argument, the module receives the object itself.
$asyncResponse->call('Dashboard', 'add', [$chart]);
```

Pagelets define instances the same way, with `$pagelet->instance()`.

## Defining modules
Send data the browser can require as a module, e.g. the configuration of the page or the current user. A module
defined again, also in a later response, replaces the previous one:

```php
<?php
$asyncResponse->bigPipe()->define('SiteData', [
    'locale' => 'sk_SK',
    'user' => ['id' => 7],
]);

// Passed to another module with a module transport marker.
$asyncResponse->call('Dashboard', 'init', [
    \dobron\BigPipe\TransportMarker::module('SiteData'),
]);
```

```javascript
import { requireModule } from 'bigpipe-util/dist/ModuleRegistry';

requireModule('SiteData').locale; // "sk_SK"
```

An element of the page can be defined as a module too. The browser finds it by its id when the module is first
required, so any module can require it by name instead of looking it up, and it is an error if it is not on the page:

```php
<?php
$asyncResponse->defineElement('ChartBox', 'chart-box');
// the same as define('ChartBox', TransportMarker::element('chart-box'))
$asyncResponse->call('Dashboard', 'show');
```

```javascript
requireModule('ChartBox').classList.add('visible');
```

Defining the name again replaces it, e.g. after the element was rendered anew. Pagelets define elements the same way
with `$pagelet->defineElement()`. To give a module an element for one call only, pass `TransportMarker::element()` as an
argument.

## Errors
Mark a response as failed with `setError()`. The browser calls the error handler of the request instead of its
handler, with the summary, description and flags. The DOM operations and modules of the response are still applied,
so you can e.g. mark the invalid field:

```php
<?php
$asyncResponse = new \dobron\BigPipe\AsyncResponse();

$asyncResponse
    ->setContent('#title-error', 'At most 80 characters.')
    ->setError(
        'Could not save the post',
        'The title is too long.',
        code: 1001,           // optional, your own code (not 0)
        isTransient: false,   // true when trying again may help
    );

$asyncResponse->send();
```

## Request API
In your frontend JavaScript, you can use the `AsyncRequest` class to send XHR requests.

```javascript
import AsyncRequest from 'bigpipe-util/dist/AsyncRequest';

const request = (new AsyncRequest('/ajax/remove.php'))
  // or .setURI('/ajax/remove.php')
  .setMethod('POST')
  .setData({
    param: 'value',
  })
  .setInitialHandler(() => {
      // pre-request callback function
  })
  .setHandler((jsonResponse) => {
      // A function to be called if the request succeeds
  })
  .setErrorHandler((xhr) => {
      // A function to be called if the request fails
  })
  .setFinallyHandler((xhr) => {
      // after request callback function
  })
  .send();

if (OH_NOES_WE_NEED_TO_CANCEL_RIGHT_NOW_OR_ELSE) {
  request.abort();
}
```

## What all can be Ajaxifed?
You can apply the BigPipe functionality to various elements in your application to enhance its interactivity and performance. Here are some examples of elements that can be ajaxified:

### Links
You can ajaxify links using the `ajaxify` and `rel` attributes:
```html
<a href="#"
   ajaxify="/ajax/remove.php"
   rel="async">Remove Item</a>
```

### Forms
Forms can be ajaxified by adding the `rel="async"` attribute and the appropriate `action`:
```html
<form action="/submit.php"
      method="POST"
      rel="async">
    <input name="user">
    <input type="submit" name="Done">
</form>
```

### Dialogs
Dialogs can also be `ajaxified` by using the `ajaxify` and `rel` attributes:
```html
<a href="#"
   ajaxify="/ajax/dialog.php"
   rel="dialog">Open Dialog</a>
```

By ajaxifying these elements, you enable dynamic content loading and interaction without the need for full page reloads. This enhances the user experience by providing seamless and efficient updates to the page content.
