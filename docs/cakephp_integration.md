---
id: cakephp_integration
title: CakePHP Integration
sidebar_label: CakePHP Integration
---

Follow the [Installation instructions](getting_started) first, then add the plugin (CakePHP 5) in
`Application::bootstrap()`:

```php
$this->addPlugin(\dobron\BigPipe\CakePHP\BigPipePlugin::class);
```

Its middleware runs after the middleware of the application. It handles every request with a fresh context, so a
worker that serves many requests does not carry the pagelets and modules of one request into the next, and it sends
the CSRF token of `CsrfProtectionMiddleware` to the browser. Every request of `AsyncRequest` that can change data then
carries it in the `X-CSRF-Token` header, which CakePHP checks.

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
