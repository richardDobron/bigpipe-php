---
id: laravel_integration
title: Laravel Integration
sidebar_label: Laravel Integration
---

To integrate BigPipe into your existing Laravel application, follow the [Installation instructions](getting_started).

However, we recommend creating your own class that extends the basic class definitions and includes an overridden `send()` method for processing the response:

1. Create the file `app/Arch/BigPipe/AsyncResponse.php`:

    ```php
    <?php
    
    namespace App\Arch\BigPipe;
    
    use Illuminate\Http\Response;
    
    class AsyncResponse extends \dobron\BigPipe\AsyncResponse
    {
        /**
         * Send response
         */
        public function send(int $status = 200): Response
        {
            return response($this->buildResponseString(), $status)
                ->withHeaders(static::headers());
        }
    }
    
    ```

2. Create the file `app/Arch/BigPipe/DialogResponse.php`:

    ```php
    <?php
    
    namespace App\Arch\BigPipe;
    
    use Illuminate\Http\Response;
    
    class DialogResponse extends \dobron\BigPipe\DialogResponse
    {
        /**
         * Send response
         */
        public function send(int $status = 200): Response
        {
            return response($this->buildResponseString(), $status)
                ->withHeaders(static::headers());
        }
    }
    
    ```

This approach allows you to seamlessly integrate BigPipe into your Laravel application while maintaining a consistent coding structure.

## Example of Class Implementation

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Arch\BigPipe\DialogResponse;

class UserController extends Controller
{
    public function __construct(
        private DialogResponse $response
    ) {
    }

    public function showUserDetailsDialog(User $user)
    {
        $this->response
            ->setContent('User: ' . $user->email)
            ->dialog();

        return $this->response->send();
    }
}

```

## CSRF protection
Send the CSRF token of the session to the browser, e.g. in a middleware or a view composer. Every request of
`AsyncRequest` that can change data then carries it in the `X-CSRF-TOKEN` header, which Laravel checks:

```php
\dobron\BigPipe\BigPipe::setCSRFToken(csrf_token());
```

The token is only sent to URLs of the same origin. To send it as a field of the data instead, e.g. `_token`, use
`setCSRFToken(csrf_token(), header: null, param: '_token')`.

## Long-running servers (Octane, FrankenPHP, RoadRunner, Swoole)

BigPipe collects the `require` calls and pagelets of a request in a `dobron\BigPipe\Context`. By default there is one
context per PHP process, which is fine for PHP-FPM, but a worker that serves many requests would carry leftovers
from one request into the next. Bind the context to the request scope in a service provider:

```php
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Context;

public function register()
{
    $this->app->scoped(Context::class, fn () => new Context());

    BigPipe::setContextResolver(fn () => app(Context::class));
}
```

Outside of Laravel, wrap the request handling in `BigPipe::withContext()`. It runs the callback with a fresh context
and restores the previous one afterwards, even when the callback throws:

```php
$response = BigPipe::withContext(fn () => $kernel->handle($request));
```
