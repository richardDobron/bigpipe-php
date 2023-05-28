---
id: getting_started
title: Integrating into your app
sidebar_label: Getting started
---

## ✅ Requirements
* PHP 7.1 or higher
* Webpack

## 📦 Installing

Follow these steps to integrate BigPipe into your application:

### 1. Install composer package

Open your terminal and run the following command to install the `Composer` package:

```shell
$ composer require richarddobron/bigpipe
```

### 2. Install npm package

In your terminal, install the `npm` package for BigPipe:

```shell
$ npm install bigpipe-util
```

### 3. Modify your entrypoint

In your entrypoint file (e.g., `/path/to/resources/js/app.js`), add the following code to set up BigPipe:

```javascript
import Primer from 'bigpipe-util/src/Primer';

Primer();

window.require = (modulePath) => {
    return modulePath.startsWith('bigpipe-util/')
        ? require('bigpipe-util/' + modulePath.substring(13) + '.js').default
        : require('./' + modulePath).default;
};
```

Create your first module in the same directory (e.g., `/path/to/resources/js/MyModule.js`):

```javascript
export default class MyModule {
    init(...args) {
        console.log('Hello world!');
        console.warning(args);
    }
}
```

call it using:
    
```php
<?php
$asyncResponse = new \dobron\BigPipe\AsyncResponse();

$asyncResponse->bigPipe()->require("require('MyModule').init()", [
    'first argument',
    'second argument',
    ...
]);
```

### 4. Add this code to page footer

In your page footer, add the following code to set up BigPipe:

```php
<script>
    (new (require("bigpipe-util/src/ServerJS"))).handle(<?=json_encode(\dobron\BigPipe\BigPipe::jsmods())?>);
</script>
```

🎉 Congratulations! You've successfully integrated BigPipe into your application. Enjoy improved web performance and user experiences.

_Feel free to adjust the wording and formatting as needed to match your documentation's style._
