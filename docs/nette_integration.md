---
id: nette_integration
title: Nette Integration
sidebar_label: Nette Integration
---

Follow the [Installation instructions](getting_started) first, then register the extension (Nette Application 3.1 or
later with Latte 3):

```neon
extensions:
    bigpipe: dobron\BigPipe\Nette\DI\BigPipeExtension
```

## Responses

Send an `AsyncResponse` or a `DialogResponse` from a presenter with `BigPipeResponse`. It is streamed when the browser
asks for a [streamed response](pagelets.md#streamed-responses):

```php
use dobron\BigPipe\DialogResponse;
use dobron\BigPipe\Nette\BigPipeResponse;

final class UserPresenter extends Nette\Application\UI\Presenter
{
    public function actionDialog(int $id): void
    {
        $user = $this->users->get($id);

        $response = (new DialogResponse())
            ->setTitle('User')
            ->setBody('E-mail: ' . htmlspecialchars($user->email))
            ->dialog();

        $this->sendResponse(new BigPipeResponse($response));
    }
}
```

## Latte functions

```latte
{* calls a JavaScript module once the page is loaded *}
{do jsmod('Feed', 'init', [$feed->id])}

{* data the browser can require as a module *}
{do jsdefine('Config', [locale: $locale])}

{* at the end of the layout: the pagelets and the modules of the page *}
{bigpipe()}
```

The arguments are those of `call()` and `define()`. Without the DI extension, add the Latte extension yourself:
`$latte->addExtension(new dobron\BigPipe\Nette\Latte\BigPipeLatteExtension())`.

## CSRF protection

Nette protects forms and signals with their own tokens. To protect the requests of `AsyncRequest` too, send a token
with `BigPipe::setCSRFToken()` in the `startup()` of a base presenter and check it, see [Getting started](getting_started).
