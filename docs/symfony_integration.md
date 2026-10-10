---
id: symfony_integration
title: Symfony Integration
sidebar_label: Symfony Integration
---

Follow the [Installation instructions](getting_started) first, then enable the bundle (Symfony 6.4 or later):

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
- sends a [CSRF token](#csrf-protection) to the browser, when configured
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
`BigPipe::setNonce()` before `bigpipe()`, see [Getting started](getting_started).

## CSRF protection

Symfony checks CSRF tokens where the application asks for them, so the bundle only sends one when you name its id:

```yaml
# config/packages/bigpipe.yaml
bigpipe:
    csrf:
        token_id: bigpipe       # null (the default) sends none
        header: X-CSRF-TOKEN    # or null, to send it as a field of the data only
        param: ~                # e.g. _token
```

Every request of `AsyncRequest` that can change data then carries the token. Check it in the controller:

```php
if (!$this->isCsrfTokenValid('bigpipe', $request->headers->get('X-CSRF-TOKEN'))) {
    throw $this->createAccessDeniedException();
}
```

It needs `symfony/security-csrf` and `framework.csrf_protection`.

## Worker mode (FrankenPHP, RoadRunner, Swoole)

BigPipe collects the `require` calls and pagelets of a request in a `dobron\BigPipe\Context`. A worker that serves many
requests would carry leftovers from one request into the next, so the bundle keeps the context in a service that
Symfony resets between requests (`kernel.reset`).
