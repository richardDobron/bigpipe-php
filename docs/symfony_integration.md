---
id: symfony_integration
title: Symfony Integration
sidebar_label: Symfony Integration
---

Follow the [Installation instructions](getting_started.md) first, then enable the bundle (Symfony 6.4 or later):

```php
// config/bundles.php
return [
    // ...
    dobron\BigPipe\Symfony\BigPipeBundle::class => ['all' => true],
];
```

The bundle:

- keeps the pagelets and modules of every request in a [context of that request](#worker-mode-frankenphp-roadrunner-swoole),
  also in worker mode
- turns a response of BigPipe a controller returns into a response of Symfony
- sends a [CSRF token](#csrf-protection) to the browser, when configured, answers the URL the browser gets a new token
  from, and answers an [expired token](#expired-tokens) of a request of BigPipe so that the browser sends it again
- registers the [Twig functions](#twig-functions)

## Responses

Return `dobron\BigPipe\Symfony\AsyncResponse` and `dobron\BigPipe\Symfony\DialogResponse` from a controller. They are
streamed when the browser asks for a [streamed response](pagelets.md#streamed-responses). `send()` returns the response
too, with a status:

```php
<?php

namespace App\Controller;

use App\Entity\User;
use dobron\BigPipe\Symfony\DialogResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/users/{id}/dialog')]
    public function showUserDetailsDialog(User $user): DialogResponse
    {
        return (new DialogResponse())
            ->setTitle('User')
            ->setBody('E-mail: ' . htmlspecialchars($user->getEmail()))
            ->dialog();
    }
}
```

## Twig functions

```twig
{# calls a JavaScript module once the page is loaded #}
{{ bigpipe_jsmod('Feed', 'init', [feed.id]) }}

{# data the browser can require as a module #}
{{ bigpipe_define('Config', {locale: app.request.locale}) }}

{# at the end of the page: the pagelets and the modules of the page #}
{{ bigpipe() }}
```

The arguments are those of `call()` and `define()`. For a Content Security Policy, set the nonce of the request with
`BigPipe::setNonce()` before `bigpipe()`, see [Getting started](getting_started.md).

## CSRF protection

Symfony checks CSRF tokens where the application asks for them, so the bundle only sends one when you name its id:

```yaml
# config/packages/bigpipe.yaml
bigpipe:
    csrf:
        token_id: bigpipe       # null (the default) sends none
        header: X-CSRF-TOKEN    # or null, to send it as a field of the data only
        param: ~                # e.g. _token
        refresh_uri: /bigpipe/csrf-token   # null answers none
        retry: true             # false leaves a rejected token to the application
```

Every request of `AsyncRequest` that can change data then carries the token. Check it in the controller, and reject an
invalid token with `419`, the status the browser recovers from:

```php
use Symfony\Component\HttpKernel\Exception\HttpException;

if (!$this->isCsrfTokenValid('bigpipe', $request->headers->get('X-CSRF-TOKEN'))) {
    throw new HttpException(419, 'Invalid CSRF token.');
}
```

It needs `symfony/security-csrf` and `framework.csrf_protection`.

### Expired tokens

A page that stays open after the session expired sends a token the controller rejects. The browser can get a new token
and send the request again, once, so the user does not lose what they typed. The bundle does both:

- It answers a `GET` of `csrf.refresh_uri` (`/bigpipe/csrf-token`, no route needed) with a new token. On a `419` the
  browser requests it and then repeats the request.
- A request of BigPipe (`X-Requested-With: XMLHttpRequest`) that ends with an `HttpException` with status `419` is
  answered with the new token (`csrf.retry`): the browser applies it and sends the request again, and the handlers of the
  request see the response of the second try.

Other exceptions, such as the `403` of `createAccessDeniedException()`, are left alone. If the token is rejected again,
the second response is handled like any other, so a request never loops.

## Worker mode (FrankenPHP, RoadRunner, Swoole)

BigPipe collects the `require` calls and pagelets of a request in a `dobron\BigPipe\Context`. A worker that serves many
requests would carry leftovers from one request into the next, so the bundle keeps the context in a service that
Symfony resets between requests (`kernel.reset`).
