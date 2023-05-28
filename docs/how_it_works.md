---
id: how_it_works
title: How it works
sidebar_label: How it works
---

The BigPipe integration works by utilizing the `dobron\BigPipe\AsyncResponse` class to process requests and generate the defined instructions. These instructions are then handled by the `ServerJS` class on the frontend.


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
    
    $asyncResponse->bigPipe()->require("require('UserLoggedInAlert')", [
        'Marvin', // username argument
    ]);

    // If this request was made via AsyncRequest, call the send() method
    $asyncResponse->send();
    ```

## Request API
In your frontend JavaScript, you can use the `AsyncRequest` class to send XHR requests.

```javascript
import AsyncRequest from 'bigpipe-util/src/AsyncRequest';

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
