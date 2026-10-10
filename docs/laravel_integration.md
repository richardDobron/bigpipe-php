---
id: laravel_integration
title: Laravel Integration
sidebar_label: Laravel Integration
---

Follow the [Installation instructions](getting_started) first. Laravel 9 or later discovers
`dobron\BigPipe\Laravel\BigPipeServiceProvider` on its own. The provider:

- keeps the pagelets and modules of every request in a [context of that request](#long-running-servers-octane-frankenphp-roadrunner-swoole),
  also on Octane
- adds the `SetUpBigPipe` middleware to the `web` group, which sends the [CSRF token](#csrf-protection) to the browser
- registers the route the browser gets a new token from, and answers an [expired token](#expired-tokens) of a request
  of BigPipe so that the browser sends the request again
- registers the [Blade directives](#blade-directives)

To change the defaults, publish the configuration:

```shell
$ php artisan vendor:publish --tag=bigpipe-config
```

## Responses

Return `dobron\BigPipe\Laravel\AsyncResponse` and `dobron\BigPipe\Laravel\DialogResponse` from a controller. They are
`Responsable`, and are streamed when the browser asks for a [streamed response](pagelets.md#streamed-responses).
`send()` returns the response too, with a status:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use dobron\BigPipe\Laravel\DialogResponse;

class UserController extends Controller
{
    public function showUserDetailsDialog(User $user, DialogResponse $response)
    {
        return $response
            ->setTitle('User')
            ->setBody('E-mail: ' . e($user->email))
            ->dialog();
    }
}
```

See [Laravel recipes](laravel_recipes.md) for complete examples: the layout and the middleware, forms with validation,
dialogs, an infinite feed, a dashboard of streamed pagelets, page transitions, live notifications, file uploads and tests.

## Blade directives

```blade
{{-- calls a JavaScript module once the page is loaded --}}
@jsmod('Feed', 'init', [$feed->id])

{{-- only when the condition holds --}}
@jsmodIf($user->isAdmin(), 'AdminToolbar')

{{-- data the browser can require as a module --}}
@define('Config', ['locale' => app()->getLocale()])

{{-- at the end of the page: the pagelets and the modules of the page --}}
@bigpipe
```

`@bigpipe` prints `BigPipe::render()`, with the Content Security Policy nonce of Vite (`Vite::useCspNonce()`) unless
one was set with `BigPipe::setNonce()`. The arguments of the directives are those of `call()` and `define()`.

## CSRF protection

The `SetUpBigPipe` middleware sends the CSRF token of the session to the browser. Every request of `AsyncRequest` that
can change data then carries it in the `X-CSRF-TOKEN` header, which Laravel checks. The token is only sent to URLs of the
same origin. To send it as a field of the data instead, set `csrf.header` to `null` and `csrf.param` to `'_token'`.

### Expired tokens

A page that stays open after the session expired sends a token Laravel rejects with `419 Page Expired`. The browser can
get a new token and send the request again, once, so the user does not lose what they typed. The provider does both:

- On a `419` the browser requests `csrf.refresh_uri` (`/bigpipe/csrf-token`, the route `bigpipe.csrf-token`), which
  answers with a new token, and then repeats the request.
- A request of BigPipe rejected with `419` is answered with the new token (`csrf.retry`): the browser applies it and
  sends the request again, and the handlers of the request see the response of the second try.

If the token is rejected again, the second response is handled like any other, so a request never loops.

Laravel 13 accepts a same-origin request of a modern browser on its origin alone (`Sec-Fetch-Site`), whatever its token
is, so an expired token mostly matters for other clients, older browsers, or an application that checks the token
itself, as the [demo application](https://github.com/richardDobron/bigpipe-php/tree/main/demo-app) does to show the
recovery.

## Long-running servers (Octane, FrankenPHP, RoadRunner, Swoole)

BigPipe collects the `require` calls and pagelets of a request in a `dobron\BigPipe\Context`. A worker that serves many
requests would carry leftovers from one request into the next, so the provider binds the context as a scoped instance,
which Laravel flushes at the start of every request.

Outside of Laravel, wrap the request handling in `BigPipe::withContext()`. It runs the callback with a fresh context
and restores the previous one afterwards, even when the callback throws:

```php
$response = BigPipe::withContext(fn () => $kernel->handle($request));
```

## Without the provider

To set it up yourself, add the provider to `dont-discover` in the `composer.json` of the application:

```json
"extra": {
    "laravel": {
        "dont-discover": ["richarddobron/bigpipe"]
    }
}
```
