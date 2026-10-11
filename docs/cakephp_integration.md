---
id: cakephp_integration
title: CakePHP Integration
sidebar_label: CakePHP Integration
---

Follow the [Installation instructions](getting_started.md) first, then add the plugin (CakePHP 5) in
`Application::bootstrap()`:

```php
$this->addPlugin(\dobron\BigPipe\CakePHP\BigPipePlugin::class);
```

Its middleware runs after the middleware of the application. It handles every request with a fresh context, so a
worker that serves many requests does not carry the pagelets and modules of one request into the next, and it sends
the CSRF token of `CsrfProtectionMiddleware` to the browser. Every request of `AsyncRequest` that can change data then
carries it in the `X-CSRF-Token` header, which CakePHP checks.

### Expired tokens

A page that stays open after the CSRF cookie expired sends a token `CsrfProtectionMiddleware` rejects. The browser can get
a new token and send the request again, once, so the user does not lose what they typed. When the application adds
`CsrfProtectionMiddleware` in `Application::middleware()`, the plugin places `CsrfRecoveryMiddleware` before it and
before `RoutingMiddleware`, which does both:

- It answers a `GET` of `/bigpipe/csrf-token` (no route needed) with a new token. When a request ends with `419` (CakePHP
  itself answers a rejected token with `403`), the browser requests it and then repeats the request.
- A request of BigPipe (`X-Requested-With: XMLHttpRequest`) rejected with `InvalidCsrfTokenException` is answered with
  the new token: the browser applies it and sends the request again, and the handlers of the request see the response
  of the second try.

Both get the token through `CsrfProtectionMiddleware`, which sets a new cookie when the old one is missing or invalid.
If the token is rejected again, the second response is handled like any other, so a request never loops. With
`CsrfProtectionMiddleware` applied in the routes instead, the plugin cannot reach it and recovers nothing. To turn either
off:

```php
$this->addPlugin(\dobron\BigPipe\CakePHP\BigPipePlugin::class, [
    'csrf' => ['refresh_uri' => null, 'retry' => false],
]);
```

## Responses

Return `send()` of `dobron\BigPipe\CakePHP\AsyncResponse` or `DialogResponse` from a controller. It is streamed when the
browser asks for a [streamed response](pagelets.md#streamed-responses):

```php
use dobron\BigPipe\CakePHP\DialogResponse;

class UsersController extends AppController
{
    public function dialog(int $id)
    {
        $user = $this->Users->get($id);

        return (new DialogResponse())
            ->setTitle('User')
            ->setBody('E-mail: ' . h($user->email))
            ->dialog()
            ->send();
    }
}
```

## View helper

```php
// src/View/AppView.php
public function initialize(): void
{
    $this->addHelper('BigPipe', ['className' => \dobron\BigPipe\CakePHP\View\Helper\BigPipeHelper::class]);
}
```

```php
<?php $this->BigPipe->jsmod('Feed', 'init', [$feed->id]) ?>
<?php $this->BigPipe->define('Config', ['locale' => $locale]) ?>

<!-- at the end of the layout: the pagelets and the modules of the page -->
<?= $this->BigPipe->render() ?>
```
