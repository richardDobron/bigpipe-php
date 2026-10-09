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
