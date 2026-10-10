---
id: pagelets
title: 🧩 Pagelets API
sidebar_label: Pagelets
---

A pagelet is an independent part of the page, like a sidebar or a feed, with its own content, CSS and JavaScript.
The page is sent with an empty placeholder for every pagelet, and the browser part of BigPipe fills the placeholders
once their CSS is loaded.

## Creating a pagelet

```php
<?php
use dobron\BigPipe\Pagelet;

$sidebar = (new Pagelet('sidebar'))
    ->appendContent('<h2>Friends</h2>')
    ->appendFile(__DIR__ . '/views/friends.php')  // the file is included and its output appended
    ->addCss('/css/sidebar.css')
    ->addJs('/js/sidebar.js')
    ->call('Tooltips', 'init')           // a module of the page bundle, runs when the pagelet is displayed
    ->onLoad(['Sidebar', 'init']);       // a module in /js/sidebar.js, runs once the file is loaded
?>

<aside><?= $sidebar ?></aside>  <!-- prints <div id="pagelet_sidebar"></div> -->
```

The modules called with `call()` run as soon as the pagelet is displayed, so they must be in the bundle of the page
or [bootloadable](bootloader.md#bootloadable-modules). The JS files of the pagelets load once every pagelet is
displayed; call the modules in those files with `onLoad()`, and use `onAfterLoad()` for work that can wait until the
window has loaded. `setJSNonBlock()` loads the JS files of a pagelet right after it is displayed, without waiting for
the other pagelets.

The placeholder is the root element of the pagelet, with the id `pagelet_` followed by the id of the pagelet, so the
pagelet can be found again, e.g. to [refresh](#pagelets-in-a-response) it. A pagelet id starts with a letter and has
only letters, digits, `_` and `-`.

At the end of the page, print the script that sends the pagelets to the browser:

```php
<?= \dobron\BigPipe\BigPipe::render() ?>
```

## Pagelet classes

A pagelet used in more places, or loaded and refreshed on its own, is best written as a class. It renders its content
in `content()`, which is called when the pagelet is rendered, like [deferred content](#deferred-content), and it
declares its CSS and JS:

```php
<?php
use dobron\BigPipe\Pagelet;

class FeedPagelet extends Pagelet
{
    protected array $css = ['/css/feed.css'];
    protected array $js = ['/js/feed.js'];

    protected function content(): string
    {
        $this->require(['Feed', 'init']);

        return view('feed', ['posts' => Post::latest()->get()])->render();
    }
}
?>

<main><?= new FeedPagelet() ?></main>
```

`content()` can also print the content instead of returning it. Without an id, a pagelet class gets one from its name,
e.g. `feed` for `FeedPagelet` and `user_profile` for `UserProfilePagelet`. Set another one with
`protected string $id = '...';` or `new FeedPagelet('main_feed')`.

## Deferred content

Content added with `appendContent()` is rendered right away, while the page is still being built. `defer()` renders
it only when the pagelet itself is rendered, which lets a [streamed](#streaming) page reach the browser before its
pagelets are ready. The callable gets the pagelet, so it can add CSS or modules too; what it prints and what it
returns are both appended:

```php
<?php
$feed = (new Pagelet('feed'))->defer(function (Pagelet $feed) {
    $feed->addCss('/css/feed.css');

    return renderFeed(loadPosts()); // e.g. slow queries
});
```

## Errors

A pagelet whose `content()` or deferred content throws does not break the page: its content and modules are replaced
by its fallback, empty by default, the exception is reported, and the other pagelets are rendered and streamed as
usual.

```php
<?php
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Pagelet;

class FeedPagelet extends Pagelet
{
    protected mixed $fallback = '<p>The feed is not available right now.</p>';
}

$ads = (new Pagelet('ads'))
    ->defer(fn () => renderAds())
    ->setFallback(fn (Throwable $exception, Pagelet $pagelet) => '');

BigPipe::setErrorHandler(fn (Throwable $exception, Pagelet $pagelet) => report($exception));
```

Without an error handler, the exception is written with `error_log()`. A handler that throws the exception again
breaks the page instead, e.g. while developing. `hasFailed()` tells whether a pagelet fell back.

## Streaming

With `BigPipe::render()`, the browser gets the pagelets at the end of the request, so the slowest pagelet holds up all
of them. `BigPipe::stream()` sends every pagelet as soon as it is rendered instead: the page printed so far is flushed
first, then each pagelet in its own `<script>`, flushed right away. Use it in place of `render()` and give the slow
pagelets [deferred content](#deferred-content), so the page reaches the browser before they are rendered:

```php
<?php
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Pagelet;

$feed = (new Pagelet('feed'))->defer(fn () => renderFeed(loadPosts()));
$ads = (new Pagelet('ads'))->defer(fn () => renderAds());
?>
<html>
<body>
    <main><?= $feed ?></main>
    <aside><?= $ads ?></aside>

    <?php BigPipe::stream(); ?>
</body>
</html>
```

Pagelets created while another one is rendered, e.g. a pagelet inside the feed, are sent after it. The last script
marks the end of the page and runs the modules of the page.

The output has to reach the browser unbuffered:

- Send the `X-Accel-Buffering: no` header behind nginx, and turn off buffering in other proxies.
- Compression like `zlib.output_compression` buffers the output, turn it off for streamed pages.
- A framework that buffers the response needs a streamed response. `stream()` accepts a callback that gets every
  chunk, by default it prints and flushes it.

In Laravel:

```php
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Pagelet;

return response()->stream(function () {
    $feed = (new Pagelet('feed'))->defer(fn () => view('feed', ['posts' => Post::latest()->get()])->render());

    echo view('page', ['feed' => $feed])->render(); // the page with the placeholders, without </body></html>
    BigPipe::stream();
    echo '</body></html>';
}, 200, ['X-Accel-Buffering' => 'no']);
```

The browser part shows each pagelet as soon as it arrives, as long as `require` exists by then: load the entrypoint
with a classic `<script src>` in the `<head>`. A `<script type="module">` runs only after the whole page is parsed.

## Without JavaScript

Crawlers and browsers without JavaScript would only see the empty placeholders. Turn pipelining off for them before
the page is rendered: every pagelet is then rendered right in its placeholder, with `<link>` tags for its stylesheets,
also its deferred content and the pagelets inside it. The page script still loads its JS files and runs its modules,
for the clients that do run JavaScript.

```php
<?php
use dobron\BigPipe\BigPipe;

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isCrawler = (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|embedly|preview/i', $userAgent);

BigPipe::setPipelining(!$isCrawler && !isset($_COOKIE['nojs']));
```

Browsers without JavaScript can't be recognized from the request. Send them to a version of the page without
pipelining from a `<noscript>` in the `<head>`, e.g. with a parameter that sets the `nojs` cookie:

```html
<noscript><meta http-equiv="refresh" content="0; url=?nojs=1"></noscript>
```

```php
<?php
if (isset($_GET['nojs'])) {
    setcookie('nojs', '1', ['path' => '/', 'samesite' => 'Lax']);
}

BigPipe::setPipelining(!isset($_GET['nojs']) && !isset($_COOKIE['nojs']));
```

Without pipelining, `stream()` still works, but the pagelets are rendered where their placeholders are printed, so
the page is only sent once they are all rendered.

A lazy pagelet is still loaded by the browser, so a crawler only sees its placeholder.

## Display order

The browser shows every pagelet as soon as its CSS is loaded, in any order. A pagelet can wait for others:

- `setPhase(int $phase)`: the pagelet is displayed after the pagelets of a lower phase, e.g. the content of the page
  in phase 0 (default) before the sidebar and ads in phase 1. `render()`, `stream()` and an `AsyncResponse` send the
  pagelets in the order of their phases, pagelets of the same phase in the order they were created.
- `displayAfter(...$pagelets)`: the pagelet is displayed after the given pagelets or pagelet ids, e.g. a chat that
  needs the feed on the page. The browser waits for them to arrive, so send them too.

```php
<?php
use dobron\BigPipe\Pagelet;

class AdsPagelet extends Pagelet
{
    protected int $phase = 1;
}

$feed = new FeedPagelet();
$ads = new AdsPagelet();
$chat = (new Pagelet('chat'))->setPhase(1)->displayAfter($feed);
```

The JS files of the pagelets still load only when all of them are displayed.

## Time to interactive

Tell the browser which phases are the content the user is waiting for, e.g. the feed in phase 0 and the sidebar and ads
in the later ones:

```php
<?php
use dobron\BigPipe\BigPipe;

BigPipe::setTtiPhase(0);   // the pagelets of the phases up to this one
```

The browser informs `tti_bigpipe` once those pagelets are displayed (see the [time to interactive in
`bigpipe-util`](https://github.com/richardDobron/bigpipe-util/blob/main/docs/pagelets.md#time-to-interactive)), e.g. to
measure it, and then downloads the JS files of the pagelets of the later phases in the background, without running them.
They are in the cache when the pagelets load their JS after all of them are displayed, and they never compete with the
first content for the network. The phase is sent with every pagelet of the page, a response with pagelets, and a page
transition. `BigPipe::setTtiPhase(null)` turns it off.

## Pagelets in a response

An `AsyncResponse` sends a pagelet with `pagelet()`: the pagelet replaces the element matching the selector, or without
one the element that sent the request, e.g. a [lazy pagelet](lazy_pagelets.md) placeholder. The browser loads its CSS
and JS and runs its modules like on a page:

```php
<?php
$response = new \dobron\BigPipe\AsyncResponse();

$response->pagelet(new FeedPagelet(), '#feed-placeholder');

$response->send();
```

`refreshPagelet()` renders a pagelet that is on the page again, e.g. after a post was added to the feed. It replaces the
root element with the same id, `pagelet_feed` for `new FeedPagelet()`:

```php
<?php
$response = new \dobron\BigPipe\AsyncResponse();

$response->refreshPagelet(new FeedPagelet());

$response->send();
```

## Streamed responses

A request that accepts a streamed response, e.g. a [page transition](page_transitions.md) or an `AsyncRequest` with
the `stream` option, sends the `__stream` parameter. `send()` then streams the response: the payload, the DOM
operations and the defines first, every pagelet as soon as it is rendered, and the modules last. The browser applies
every part when it arrives.

`stream()` streams the response for a framework that sends it itself; it accepts a callback that gets every part, like
`BigPipe::stream()`, and `AsyncResponse::isStreamRequested()` tells whether the request accepts it. In Laravel:

```php
use dobron\BigPipe\AsyncResponse;

$response = (new AsyncResponse())->transition($content, 'Feed');

if (AsyncResponse::isStreamRequested()) {
    return response()->stream(fn () => $response->stream(), 200,
        AsyncResponse::headers() + ['X-Accel-Buffering' => 'no']);
}

return response($response->buildResponseString())->withHeaders(AsyncResponse::headers());
```

Without a callback, `stream()` prints and flushes every part, and sends the headers unless they were sent already.
Like for a streamed page, the output has to reach the browser unbuffered, see [streaming](#streaming).
