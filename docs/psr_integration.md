---
id: psr_integration
title: PSR-15 Integration (Slim, Mezzio)
sidebar_label: PSR-15 (Slim, Mezzio)
---

Any framework built on PSR-7 and PSR-15, such as [Slim](https://www.slimframework.com/) or
[Mezzio](https://docs.mezzio.dev/), uses the middleware and the response factory of `dobron\BigPipe\Psr`. They need
`psr/http-server-middleware` and a PSR-17 factory, e.g. `nyholm/psr7`.

## Middleware

`BigPipeMiddleware` handles every request with a fresh context, so a worker that serves many requests (RoadRunner,
Swoole, FrankenPHP) does not carry the pagelets and modules of one request into the next. Give it the CSRF token of the
request to send it to the browser:

```php
use dobron\BigPipe\Psr\BigPipeMiddleware;

// the token of the session, which a middleware of the application set as an attribute
$app->add(new BigPipeMiddleware(fn ($request) => $request->getAttribute('csrf_token')));
```

The browser then sends the token in the `X-CSRF-TOKEN` header of every request that can change data, or as the field
named by `csrfParam`; check it where the application checks its tokens. Use a token that stays the same for the session:
a page sends the token it got when it was loaded. Without a callable, no token is sent, and the middleware has to run
after the one that provides the token.

## Responses

`ResponseFactory` turns an `AsyncResponse` or a `DialogResponse` into a PSR-7 response:

```php
use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\Psr\ResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;

$factory = new Psr17Factory();
$responses = new ResponseFactory($factory, $factory);

$app->post('/comments', function ($request) use ($responses) {
    $response = (new AsyncResponse())->appendContent('#comments', $html);

    return $responses->create($response, $request);
});
```

When the browser asks for a [streamed response](pagelets.md#streamed-responses), the body has the parts of a streamed
response, but they are sent together: a PSR-7 body is written once the response is complete.
