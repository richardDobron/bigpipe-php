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

### Expired tokens

A page that stays open after the session expired sends a token the application rejects. The browser can get a new token
and send the request again, once, so the user does not lose what they typed. Give the middleware a `ResponseFactory` (see
[Responses](#responses)) and the URL of the token:

```php
use dobron\BigPipe\Psr\BigPipeMiddleware;
use dobron\BigPipe\Psr\ResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;

$factory = new Psr17Factory(); // any PSR-17 factory of responses and streams

$app->add(new BigPipeMiddleware(
    fn ($request) => $request->getAttribute('csrf_token'),
    csrfRefreshUri: '/bigpipe/csrf-token',
    responses: new ResponseFactory($factory, $factory),
));
```

- The middleware answers a `GET` of `csrfRefreshUri` itself with the token of the request. When the application answers
  a rejected token with `419`, the browser requests this URL and repeats the request.
- A request of BigPipe (`X-Requested-With: XMLHttpRequest`) the application answers with `419` is answered with the new
  token instead (`csrfRetry`, on by default): the browser applies it and sends the request again, and the handlers of
  the request see the response of the second try.

The middleware cannot tell a rejected token from other errors, so answer a rejected token with `419`, e.g. from the
failure handler of `slim/csrf`. A `419` of a middleware that runs before this one reaches the browser, which then gets the
token from the refresh URL; a `419` of the handler or of a later middleware is answered with the new token right away. The
URL is compared with the path of the request, so with a base path, include it. If the token is rejected again, the second
response is handled like any other, so a request never loops.

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
