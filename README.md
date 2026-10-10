<img src="bigpipe.svg" alt="BigPipe logo" />

A microframework for PHP and JavaScript. Send a page in independent parts (pagelets) that are rendered in parallel and reach the browser as soon as they are ready, update the page with DOM operations, open dialogs, load the next page into the layout and call JavaScript modules, all from PHP. The browser part is the npm package [bigpipe-util](https://github.com/richardDobron/bigpipe-util).

## 👀 Demo App
Try the app with [live demo](http://bigpipe.etexweb.sk) or check how to [install](demo-app/README.md). It is a Laravel 13 application with:

- **Tutorials**: a working example of every feature (streaming pagelets, lazy pagelets, poller, morph, Bootloader, events, DOM operations, dialogs, forms, payload, transport markers, configuration, DOM references, expired CSRF tokens and redirects), each with its code and a panel that shows what the server sent back.
- **A demo app**: a blog, a shop, a dashboard of streamed pagelets, notifications and a profile, built the way an application is, from the [Laravel recipes](https://richarddobron.github.io/bigpipe-php/docs/laravel_recipes).

Every page of it is a page transition.

## 📕 Full documentation
https://richarddobron.github.io/bigpipe-php/

## ℹ️ Requirements
* PHP 8.0 or higher (8.1 for [parallel rendering](#-parallel-rendering-and-fallbacks), which uses Fibers)
* A bundler: [webpack](https://webpack.js.org/), [Vite](https://vite.dev/) or similar

## 📦 Installation
Follow these steps to install and set up:

### 1. Install composer package:
```shell
$ composer require richarddobron/bigpipe
```

### 2. Install npm package:
```shell
$ npm install bigpipe-util
```

### 3. Add the following to `/path/to/resources/js/app.js`:
```javascript
import Primer from 'bigpipe-util/dist/Primer';
import { setModuleLoader } from 'bigpipe-util/dist/ModuleRegistry';

Primer();
```

Then tell BigPipe how to load your modules by name (PHP calls e.g. `$response->call('UserLoggedInAlert')`):

**webpack**
```javascript
setModuleLoader(modulePath => require('./' + modulePath).default);
```

**Vite**
```javascript
const modules = import.meta.glob(['./**/*.js', '!./app.js'], { eager: true });

setModuleLoader(modulePath => modules[`./${modulePath}.js`]?.default);
```

> Vite needs an alias for the `events` module used by dialogs, see the
> [documentation](https://richarddobron.github.io/bigpipe-php/docs/getting_started).

### 4. Add this line to the page footer:
```html
<?= \dobron\BigPipe\BigPipe::render() ?>
```
It prints the script that sends the pagelets and the modules of the page to the browser, so load the entrypoint with a classic `<script src>` before it. See the [documentation](https://richarddobron.github.io/bigpipe-php/docs/getting_started) for module scripts and a Content Security Policy.

## 🚀 Quick start

Call a JavaScript module from PHP:

```javascript
// resources/js/UserLoggedInAlert.js
export default function UserLoggedInAlert(username) {
    alert(`Welcome, ${username}!`);
}
```

```php
$response = new \dobron\BigPipe\AsyncResponse();

$response->call('UserLoggedInAlert', null, ['Marvin']);
$response->send();
```

Update the page when a link is clicked or a form is submitted:

```html
<a href="#" ajaxify="/ajax/remove.php?id=123" rel="async">Remove</a>
```

```php
$response = new \dobron\BigPipe\AsyncResponse();

$response->remove('#item-' . (int) $_GET['id']);
$response->send();
```

Send a part of the page as soon as it is ready:

```php
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Pagelet;

$feed = (new Pagelet('feed'))->defer(fn () => renderFeed(loadPosts()));
?>
<main><?= $feed ?></main>

<?php BigPipe::stream(); ?>
```

## 🧩 Pagelets
A page made of independent parts, each with its own content, CSS and JavaScript. The page is sent with their placeholders and the pagelets follow, one by one while they are rendered with `BigPipe::stream()`, or at the end of the page with `BigPipe::render()`.

- [Pagelets](https://richarddobron.github.io/bigpipe-php/docs/pagelets): classes, deferred content, streaming, errors with fallbacks, display order and a version of the page without JavaScript.
- [Lazy pagelets](https://richarddobron.github.io/bigpipe-php/docs/lazy_pagelets): load a pagelet when it becomes visible, and infinite scroll.
- [Page transitions](https://richarddobron.github.io/bigpipe-php/docs/page_transitions): load the next page into the layout instead of in full.
- [Bootloader](https://richarddobron.github.io/bigpipe-php/docs/bootloader): a resource map, and modules loaded only when they are called.
- [Poller](https://richarddobron.github.io/bigpipe-php/docs/poller): request a URL again and again, controlled by the server.

```php
use dobron\BigPipe\Pagelet;

class FeedPagelet extends Pagelet
{
    protected array $css = ['/css/feed.css'];

    protected function content(): string
    {
        $this->call('Feed', 'init');

        return renderFeed(loadPosts());
    }
}

echo new FeedPagelet();          // <div id="pagelet_feed"></div>
echo FeedPagelet::lazy('/pagelets/feed'); // loaded when it becomes visible
```

### ⚡ Parallel rendering and fallbacks
Pagelets that wait for an API or a query do not have to wait for each other: with `BigPipe::setParallel(true)` they are
rendered concurrently, so the page takes as long as the slowest pagelet, not as long as all of them. A pagelet that
throws is replaced by its fallback, and the rest of the page is sent as usual.

```php
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Pagelet;

BigPipe::setParallel(true);

class WeatherPagelet extends Pagelet
{
    protected mixed $fallback = '<p>The weather is not available right now.</p>';

    protected function content(): string
    {
        // Started without blocking (curl_multi, an async query…), awaited while the other pagelets are rendered.
        $forecast = Pagelet::await(fn () => $this->api->poll());

        return renderWeather($forecast);
    }
}
```

`setPhase()` and `displayAfter()` decide the order in which the browser shows the pagelets, and
`$response->refreshPagelet(new FeedPagelet())` renders a pagelet again and replaces it on the page. See
[Pagelets](https://richarddobron.github.io/bigpipe-php/docs/pagelets).

## 🧭 Page transitions
The links of the site load only the content of the next page into the layout, which stays with its scripts and state:

```php
use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\Quickling;
?>
<main id="content"><?= $content ?></main>
<?php Quickling::init('content'); // in the layout: does nothing in a page transition ?>
```

```php
// the controller answers a page transition with the content only
if (Quickling::isRequested()) {
    return (new AsyncResponse())->transition($content, 'Feed')->send();
}
```

## 🔁 Poller
Request a URL again and again; every response is applied like any other, and the server can slow the poller down or stop it:

```php
use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\Poller;

(new Poller('/notifications/poll', 30000))->setMuteWhenIdle(5 * 60 * 1000)->start();

// the endpoint
$response = (new AsyncResponse())->setContent('#unread', (string) $unread);

if ($unread === 0) {
    $response->call(Poller::requestedId(), 'setInterval', [120000]); // or 'stop', 'mute'
}

$response->send();
```

## 🪄 DOMOPS API
- **setContent**: Sets the content of an element.
- **appendContent**: Insert content as the last child of specified element.
- **prependContent**: Insert content as the first child of specified element.
- **insertAfter**: Insert content after specified element.
- **insertBefore**: Insert content before specified element.
- **remove**: Remove specified element and its children.
- **replace**: Replace specified element with content.
- **morph**: Update specified element to match content, keeping focus and form values.
- **morphContent**: Like morph, for the children of specified element only.
- **hide**, **show**: Hide or show specified element.

```php
$response = new \dobron\BigPipe\AsyncResponse();

$response->setContent('div#content', $newContent);
$response->send();
```

## 🔄 Refresh & Redirecting

```php
$response = new \dobron\BigPipe\AsyncResponse();

$response->reload(250); // reload page with 250ms delay
// or
$response->redirect('/onboarding', 500); // redirect with 500ms delay

$response->send();
```

## ℹ️ Payload & errors

```php
$response = new \dobron\BigPipe\AsyncResponse();

$response->setPayload([
    'username' => $_POST['username'],
    'status' => 'unavailable',
    'message' => 'Username is unavailable.',
]);

$response->send();
```

`setError()` marks a response as failed, so the browser calls the error handler of the request instead of its handler:

```php
$response = new \dobron\BigPipe\AsyncResponse();

$response
    ->setContent('#title-error', 'At most 80 characters.')
    ->setError('Could not save the post', 'The title is too long.');

$response->send();
```

## 🛠️ BigPipe API
- **call**: Call JavaScript module method. You can call a specific class method or a regular function with the custom arguments.

Example PHP code:
```php
$asyncResponse = new \dobron\BigPipe\AsyncResponse();

$asyncResponse->call('SecretModule', 'run', [
    'first argument',
    'second argument',
]);
// without a method, a class is created: $asyncResponse->call('UserLoggedInAlert', null, ['Marvin'])
$asyncResponse->send();
```
Example JavaScript code:
```javascript
class SecretModule {
    run(first, second) {
        // ...
    }
}
```
- **instance**: Keep one object in the browser and talk to it from more calls, also in the following responses.
- **define**: Send data the browser can require as a module, e.g. the configuration of the page.

```php
$chart = $asyncResponse->instance('ChartRenderer', [\dobron\BigPipe\TransportMarker::element('chart'), [10, 20, 30]]);
$chart->call('render');

$asyncResponse->define('SiteData', ['locale' => 'sk_SK']);
```
- **transport**: Through transport markers you can send HTML content but also transform the content into JavaScript objects (such as Map, Set or Element).

Example PHP code:
```php
$asyncResponse = new \dobron\BigPipe\AsyncResponse();
$asyncResponse->call('Chart', 'setup', [
    'element' => \dobron\BigPipe\TransportMarker::element('chart-div'),
    'dataPoints' => \dobron\BigPipe\TransportMarker::set([
        ['x' => 10, 'y' => 71],
        ['x' => 20, 'y' => 55],
        ['x' => 30, 'y' => 50],
        ['x' => 40, 'y' => 65],
    ]),
]);
$asyncResponse->send();
```

## 🔐 Expired CSRF tokens
A page that stays open after the session expired sends a token the server rejects. Give the browser the token, and a
URL to get a new one: it sends the rejected request again, once, and the user does not lose what they typed.

```php
\dobron\BigPipe\BigPipe::setCSRFToken($token, refreshUri: '/csrf-token');

// or answer the rejected request with the new token
$response->retryWithCSRFToken($newToken)->send();
```

# ⚡️ What can be ajaxified?

## 🔗 Links
```html
<a href="#"
   ajaxify="/ajax/remove.php"
   rel="async">Remove Item</a>
```

## 📝 Forms
```html
<form action="/submit.php"
      method="POST"
      rel="async">
    <input name="user">
    <input type="submit" name="Done">
</form>
```

## 💬 Dialogs
```html
<a href="#"
   ajaxify="/ajax/modal.php"
   rel="dialog">Open Modal</a>
```

```php
// /ajax/modal.php
$response = new \dobron\BigPipe\DialogResponse();

$response->setTitle('Delete the post?')
    ->setBody('<p>The post and its comments will be removed.</p>')
    ->setFooter('<button data-dismiss="modal">Cancel</button>')
    ->setBackdrop('static')
    ->dialog();

$response->send();
```

See [Dialogs](https://richarddobron.github.io/bigpipe-php/docs/dialogs) for their options, a React body and closing them from the server.

## 🧱 Integrations
- [Laravel](https://richarddobron.github.io/bigpipe-php/docs/laravel_integration): the setup, CSRF protection and long-running servers (Octane, FrankenPHP, RoadRunner, Swoole), and [recipes](https://richarddobron.github.io/bigpipe-php/docs/laravel_recipes) for a real application.
- [React](https://richarddobron.github.io/bigpipe-php/docs/react_integration): render a React component with its props from PHP, e.g. in a dialog.

## 🌟 Inspiration

BigPipe is inspired by Facebook's BigPipe. For more details
read their blog post: [Pipelining web pages for high performance][blog].

## 💡 Motivation

There is a large number of PHP projects for which moving to modern frameworks like Laravel Livewire, React, Vue.js (and many more!) could be very challenging.

The purpose of this library is to rapidly reduce the continuously repetitive code to work with the DOM and improve the communication barrier between PHP and JavaScript.

## 📑 Version Guidance

| Version | Released   | Status     | Repo                   | PHP Version | bigpipe-util |
|---------|------------|------------|------------------------|-------------|--------------|
| 0.x     | 2022-03-27 | Maintained | [v0.x][bigpipe-0-repo] | >=7.1       | 0.x          |
| 1.x     | 2022-07-29 | Maintained | [v1.x][bigpipe-1-repo] |  ^8.0       | 0.2.x        |
| 2.x     | unreleased | Next       | main                   |  ^8.0       | 2.x          |

From 2.0, `richarddobron/bigpipe` and `bigpipe-util` share the major and minor version: a release that changes what the
server sends comes out in both, e.g. 2.1.0 of this library works with 2.1.x of `bigpipe-util`. Fixes are released on
their own as patch versions.

Deprecated methods keep working until the next major version and are marked with `@deprecated`.

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 📜 License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

[blog]: https://web.archive.org/web/20160223093221/https://www.facebook.com/notes/facebook-engineering/bigpipe-pipelining-web-pages-for-high-performance/389414033919
[bigpipe-0-repo]: https://github.com/richarddobron/bigpipe-php/tree/0.x
[bigpipe-1-repo]: https://github.com/richarddobron/bigpipe-php
