---
id: example_configuration
title: Configuration
sidebar_label: Configuration
---

This section introduces a dynamic configuration method that simplifies defining and accessing application settings. With this approach, you can easily set and retrieve various configuration parameters based on your application's requirements.

Here's the code example illustrating how to use this dynamic configuration:

```javascript
let config = {};

class PageConfig {
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

    getDebugInfo() {
        return config;
    }
}

export default PageConfig;
```

On the backend, you can define and initialize this configuration with specific values, like this:

```php
$asyncResponse = new \dobron\BigPipe\AsyncResponse();
$asyncResponse->bigPipe()->require(
    ['PageConfig', 'set'],
    [
        [
            'pageId' => 1234567890,
            'category' => 'IT',
        ],
    ]
);
```

This example provides a clear overview of how dynamic configuration simplifies setting and retrieving application settings.

```javascript
import PageConfig from './PageConfig';

new PageConfig().set('title', 'Hello World!')

console.log(new PageConfig().get('pageId'));
```
