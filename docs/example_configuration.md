---
id: example_configuration
title: Configuration
sidebar_label: Configuration
---

Send the configuration of the page from PHP and read it anywhere in your JavaScript. `define()` makes data available as
a module, on the page or in any response. A module defined again, also in a later response, replaces the previous one.

```php
<?php
use dobron\BigPipe\BigPipe;

BigPipe::page()->define('PageConfig', [
    'pageId' => 1234567890,
    'category' => 'IT',
]);
```

Require it in the browser by its name:

```javascript
import { requireModule } from 'bigpipe-util/dist/ModuleRegistry';

console.log(requireModule('PageConfig').pageId);
```

Pass it to another module with a module transport marker, so the module doesn't have to know where it comes from:

```php
<?php
$response = new \dobron\BigPipe\AsyncResponse();

$response->call('Dashboard', 'init', [
    \dobron\BigPipe\TransportMarker::module('PageConfig'),
]);

$response->send();
```

## Settings changed at runtime

For a configuration that changes over time, write a module that keeps the values and let responses update it:

```javascript title="resources/js/Settings.js"
const config = {};

export default class Settings {
    get(key, defaultValue) {
        return key in config ? config[key] : defaultValue;
    }

    set(key, value) {
        if (typeof key === 'object') {
            Object.assign(config, key);
        } else {
            config[key] = value;
        }
    }
}
```

```php
<?php
$response = new \dobron\BigPipe\AsyncResponse();

$response->call('Settings', 'set', [['category' => 'Sport']]);

$response->send();
```

```javascript
import Settings from './Settings';

console.log(new Settings().get('category'));
```
