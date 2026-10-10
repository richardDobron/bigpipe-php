---
id: example_configuration
title: Configuration
sidebar_label: Configuration
---

`define()` sends data to the browser as a module of its own. PHP decides the settings of the page, e.g. the base URL of
the API and how persistent requests are, and the modules you write read them by name, without a global variable or a
file to keep in sync. A module defined again, also in a later response, replaces the previous one.

## Define the configuration

On the page, before `BigPipe::render()` prints the script:

```php
<?php
use dobron\BigPipe\BigPipe;

BigPipe::page()->define('AppConfig', [
    'apiBase' => '/api/v2',
    'locale' => 'sk_SK',
    'request' => ['timeout' => 8000, 'retries' => 2],
]);
```

## Use it in your own module

Require it where you need it, by its name. Read it when the module is used, not when the file is loaded, so that a later
`define()` is seen:

```javascript title="resources/js/Api.js"
import AsyncRequest from 'bigpipe-util/dist/async/AsyncRequest';
import { requireModule } from 'bigpipe-util/dist/ModuleRegistry';

export default class Api {
    get(path, handler) {
        const { apiBase, request } = requireModule('AppConfig');

        return new AsyncRequest(apiBase + path)
            .setMethod('GET')
            .setOption('retries', request.retries)
            .setTimeoutHandler(request.timeout)
            .setHandler(handler)
            .send();
    }
}
```

`Api` is an ordinary module of your bundle, the server can call it too:

```php
<?php
$response = new \dobron\BigPipe\AsyncResponse();

$response->call('Api', 'get', ['/notifications']);
$response->send();
```

## Change it later

Any response can define the module again, e.g. after the user switches the language or a quiet hour begins. The next
call of `Api` reads the new values:

```php
<?php
$response = new \dobron\BigPipe\AsyncResponse();

$response->define('AppConfig', [
    'apiBase' => '/api/v2',
    'locale' => 'en_US',
    'request' => ['timeout' => 20000, 'retries' => 0],
]);

$response->send();
```

## Pass it to a module

A module transport marker hands the defined module to a module as an argument, so the module doesn't have to know its
name:

```php
<?php
$response = new \dobron\BigPipe\AsyncResponse();

$response->call('Dashboard', 'init', [
    \dobron\BigPipe\TransportMarker::module('AppConfig'),
]);

$response->send();
```

```javascript title="resources/js/Dashboard.js"
export default class Dashboard {
    init(config) {
        console.log(config.locale);
    }
}
```

## Keep state in the browser

For values that change in the browser and are shared by several calls, define an instance. The browser creates it once
and later calls talk to the same object, see [Instances](how_it_works.md#instances):

```php
<?php
$settings = $response->instance('Settings', [['category' => 'IT']]);
$settings->call('set', ['category', 'Sport']);
```

```javascript title="resources/js/Settings.js"
export default class Settings {
    constructor(values) {
        this.values = values;
    }

    set(key, value) {
        this.values[key] = value;
    }

    get(key, defaultValue) {
        return key in this.values ? this.values[key] : defaultValue;
    }
}
```
