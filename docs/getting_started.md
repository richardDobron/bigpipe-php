---
id: getting_started
title: Integrating into your app
sidebar_label: Getting started
---

BigPipe has two parts: this PHP library builds the responses, and the npm package
[`bigpipe-util`](https://github.com/richardDobron/bigpipe-util) applies them in the browser. Install both, start the
browser part in your entrypoint and print the BigPipe script at the end of your pages.

## Requirements

- PHP 8.0 or higher
- A bundler, such as [webpack](https://webpack.js.org/) or [Vite](https://vite.dev/). `bigpipe-util` ships ES modules
  and CommonJS and doesn't depend on a specific bundler.

## 1. Install the packages

```shell
composer require richarddobron/bigpipe
npm install bigpipe-util
```

Use the same major and minor version of both, e.g. 2.1.x of `bigpipe-util` works with 2.1.0 of this library.

## 2. Set up the entrypoint

In your entrypoint (e.g. `resources/js/app.js`), start the Primer, which makes links and forms with `rel` asynchronous,
and tell BigPipe how to load your modules. The server refers to them by name, e.g. `$response->call('MyModule')`, and
BigPipe calls the loader to get the module, its default export.

**webpack**

```javascript title="resources/js/app.js"
import Primer from 'bigpipe-util/dist/Primer';
import { setModuleLoader } from 'bigpipe-util/dist/ModuleRegistry';

Primer();

setModuleLoader(modulePath => require('./' + modulePath).default);
```

**Vite**

```javascript title="resources/js/app.js"
import Primer from 'bigpipe-util/dist/Primer';
import { setModuleLoader } from 'bigpipe-util/dist/ModuleRegistry';

Primer();

// Eager, because modules are called synchronously. The entrypoint itself is excluded.
const modules = import.meta.glob(['./**/*.js', '!./app.js'], { eager: true });

setModuleLoader(modulePath => modules[`./${modulePath}.js`]?.default);
```

Names are loaded relative to the entrypoint, so `MyModule` is `resources/js/MyModule.js` and `Admin/Panel` is
`resources/js/Admin/Panel.js`. The modules of `bigpipe-util` that the server refers to are resolved automatically.

Vite needs an alias for the `events` module that dialogs use, and a defined `__DEV__` shows debug messages. Both are
described in the [`bigpipe-util` guide](https://github.com/richardDobron/bigpipe-util/blob/main/docs/getting_started.md).

## 3. Print the BigPipe script

At the end of the page, print the script that sends the pagelets and the modules required while the page was rendered
to the browser:

```php title="layout.php"
<?= \dobron\BigPipe\BigPipe::render() ?>
```

The entrypoint can be loaded any way: a classic `<script src>`, `async`, `defer`, or a `<script type="module">` as Vite
adds it, before or after this script. The script queues its calls in `window.__bigpipe` until the entrypoint has run:

```html
<script>(window.__bigpipe=window.__bigpipe||[]).push(["arrive",{"id":"feed",…}]);</script>
```

With an entrypoint loaded before the pagelets, e.g. a classic script in the `<head>`, every pagelet is displayed as
soon as it arrives. A deferred or module entrypoint displays the pagelets that arrived before it once it runs.
`BigPipe::setScriptType('module')` is no longer needed.

With a nonce-based Content Security Policy, set the nonce of the request before rendering. `nonceAttribute()` and
`BigPipe::render()` add it to the inline scripts, and `BigPipe::render()` also defines it as the `CSPNonce` module, from
which the browser part adds it to the stylesheets and scripts of the pagelets:

```php
\dobron\BigPipe\BigPipe::setNonce($cspNonce);
```

Only the page script defines the nonce, `AsyncResponse` never does: the page keeps the nonce of its own policy, also
when later requests have nonces of their own.

A policy that requires [Trusted Types](https://developer.mozilla.org/docs/Web/API/Trusted_Types_API) has to allow
the policy of `bigpipe-util`, which inserts the HTML and the scripts of the server:
`require-trusted-types-for 'script'; trusted-types bigpipe`.

## 4. Call your first module

A module is a default export: a function, a class or an object.

```javascript title="resources/js/MyModule.js"
export default class MyModule {
    init(...args) {
        console.log('Hello world!', args);
    }
}
```

Call it from PHP, with any arguments:

```php
<?php
$response = new \dobron\BigPipe\AsyncResponse();

$response->call('MyModule', 'init', [
    'first argument',
    'second argument',
]);

$response->send();
```

`send()` prints the response and ends the script. To call a module when the page has loaded, call it on the page
instead, before the script of step 3 is printed: `\dobron\BigPipe\BigPipe::page()->call('MyModule', 'init')`.

## 5. Make an element asynchronous

Add `rel="async"` to a link or a form, answer with an `AsyncResponse` and BigPipe applies it:

```html
<a href="#" ajaxify="/ajax/remove.php?id=123" rel="async">Remove</a>
```

```php title="/ajax/remove.php"
<?php
$response = new \dobron\BigPipe\AsyncResponse();

$response->remove('#item-' . (int) $_GET['id']);

$response->send();
```

## 6. Pipeline a part of the page

A pagelet is an independent part of the page. The page is sent with its empty placeholder, and the content, CSS and
JavaScript follow in the script from step 3:

```php title="page.php"
<?php
use dobron\BigPipe\Pagelet;

$feed = (new Pagelet('feed'))->appendContent('<p>The feed.</p>');
?>
<main><?= $feed ?></main>

<?= \dobron\BigPipe\BigPipe::render() ?>
```

Render slow pagelets with `defer()` and send them as soon as they are ready with `BigPipe::stream()`, load them when
they become visible with [lazy pagelets](lazy_pagelets.md), or load the next page into the layout with
[page transitions](page_transitions.md). See [Pagelets](pagelets.md).

## Next steps

- Update the page with the [DOMOPS API](domops.md) and open [dialogs](dialogs.md) from PHP.
- Learn how a request becomes changes of the page in [How it works](how_it_works.md).
- See a complete page in the [pipelined page example](example_page.md).
- Use BigPipe with [Laravel](laravel_integration.md) or [React](react_integration.md).
