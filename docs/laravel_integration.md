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
            ->setTitle('User')
            ->setBody('E-mail: ' . e($user->email))
            ->dialog();

        return $this->response->send();
    }
}

```

See [Laravel recipes](laravel_recipes.md) for complete examples: the layout and the middleware, forms with validation,
dialogs, an infinite feed, a dashboard of streamed pagelets, page transitions, live notifications, file uploads and tests.

## CSRF protection
Send the CSRF token of the session to the browser, e.g. in a middleware or a view composer. Every request of
`AsyncRequest` that can change data then carries it in the `X-CSRF-TOKEN` header, which Laravel checks:

```php
\dobron\BigPipe\BigPipe::setCSRFToken(csrf_token());
```

The token is only sent to URLs of the same origin. To send it as a field of the data instead, e.g. `_token`, use
`setCSRFToken(csrf_token(), header: null, param: '_token')`.

### Expired tokens

A page that stays open after the session expired sends a token Laravel rejects with `419 Page Expired`. The browser can
get a new token and send the request again, once, so the user does not lose what they typed. There are two ways, use
either or both.

Laravel 13 accepts a same-origin request of a modern browser on its origin alone (`Sec-Fetch-Site`), whatever its token
is, so an expired token mostly matters for other clients, older browsers, or an application that checks the token
itself, as the [demo application](https://github.com/richardDobron/bigpipe-php/tree/main/demo-app) does to show the
recovery.

**Name a refresh URL.** On a `419` the browser requests the URL, which answers with a new token, and then repeats the
request:

```php
// where the token is set
\dobron\BigPipe\BigPipe::setCSRFToken(csrf_token(), refreshUri: '/csrf-token');

// routes/web.php
Route::get('/csrf-token', function () {
    \dobron\BigPipe\BigPipe::setCSRFToken(csrf_token(), refreshUri: '/csrf-token');

    return (new \App\Arch\BigPipe\AsyncResponse())->send();
})->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
```

**Answer the rejected request.** Respond to the rejection with a response that carries the new token. The browser applies
it and sends the request again, and the handlers of the request see the response of the second try. Laravel hands the
handler the expired token as an `HttpException` with the status 419:

```php
// app/Exceptions/Handler.php
use Symfony\Component\HttpKernel\Exception\HttpException;

$this->renderable(function (HttpException $e, $request) {
    if ($e->getStatusCode() === 419 && $request->ajax()) {
        return (new \App\Arch\BigPipe\AsyncResponse())->retryWithCSRFToken(csrf_token())->send();
    }
});
```

If the token is rejected again, the second response is handled like any other, so a request never loops.

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
