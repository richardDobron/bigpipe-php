---
id: payload
title: ℹ️ Payload API
sidebar_label: Payload
---

The Payload API allows you to send custom data to the frontend for potential further processing in JavaScript. This data is dispatched in the form of a JSON object.

This feature enables seamless data exchange between the backend and frontend, facilitating dynamic and interactive behavior in your application.

## Live Example
You can observe the functionality of this API in the [demo page](http://bigpipe.xf.cz/tutorial/payload).

## Example
Define the payload data on the backend-side:

```php
$response = new \dobron\BigPipe\AsyncResponse();

$response->setPayload([
    'username' => $_POST['username'],
    'status' => 'unavailable',
    'message' => 'Username is unavailable.',
]);

$response->send();
```

Subsequently, you can access the response payload from the server by calling the **AsyncRequest** method:
```javascript
import AsyncRequest from "bigpipe-util/src/async/AsyncRequest";

(new AsyncRequest('payload.php'))
    .setData({
        username: 'john.doe',
    })
    .setHandler((response) => {
        const {payload} = response;

        console.log(payload);
    })
    .send();
```

