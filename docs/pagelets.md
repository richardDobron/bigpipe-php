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
    ->appendContent(__DIR__ . '/views/friends.php', true) // a file is included and its output appended
    ->addCss('/css/sidebar.css')
    ->addJs('/js/sidebar.js')
    ->require(['Sidebar', 'init']);
?>

<aside><?= $sidebar ?></aside>  <!-- prints the placeholder -->
```

At the end of the page, print the script that sends the pagelets to the browser:

```php
<?= \dobron\BigPipe\BigPipe::render() ?>
```

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
