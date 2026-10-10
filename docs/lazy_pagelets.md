---
id: lazy_pagelets
title: 💤 Lazy Pagelets API
sidebar_label: Lazy Pagelets
---

A lazy pagelet is loaded from a URL when it is needed: when it becomes visible (default), when the browser is idle,
or right away. Content below the fold or expensive to render then doesn't delay the page.

Like the `UIPagelet` of Facebook, the page only gets the root element of the pagelet, e.g.
`<div id="pagelet_feed">Loading…</div>`, and a call of the `UIPagelet` module with it. When it's time, the module
requests the URL, and the response puts the pagelet in the place of the placeholder.

## Rendering the placeholder

A [pagelet class](pagelets.md#pagelet-classes) returns its placeholder with `lazy()`:

```php
<?php
echo FeedPagelet::lazy('/pagelets/feed');

echo StatsPagelet::lazy(
    '/pagelets/stats',
    ['period' => 'week'],                // data sent with the request
    '<p>Loading…</p>',                   // content of the placeholder
    \dobron\BigPipe\LazyPagelet::LOAD_IDLE // LOAD_VISIBLE (default), LOAD_IDLE or LOAD_EAGER
);
```

Any pagelet can be loaded lazily with `LazyPagelet` and its id:

```php
<?php
use dobron\BigPipe\LazyPagelet;

echo (new LazyPagelet('stats', '/pagelets/stats'))
    ->setData(['period' => 'week'])
    ->setPlaceholder('<p>Loading…</p>')
    ->setLoad(LazyPagelet::LOAD_IDLE);
```

The call of `UIPagelet` is sent with the page, or with the response or pagelet that prints the placeholder, so it
runs once the placeholder is on the page. Print placeholders inside a pagelet in its `content()`, in `defer()`, or
in a file appended with `appendFile($file)`.

## Responding

The endpoint responds with the pagelet. `pagelet()` puts it in the place of the placeholder, and the browser loads its
CSS and JS and runs its modules like on a page:

```php
<?php
$response = new \dobron\BigPipe\AsyncResponse();

$response->pagelet(new FeedPagelet());

$response->send();
```

## Infinite scroll

`MorePager` loads the next page of a list when the user scrolls to its end, like the `MorePagerFetchOnScroll` of
Facebook. It prints a plain "See more" link handled by the Primer (`rel="async"`), and calls the
`MorePagerFetchOnScroll` module with it, which clicks the link when it comes within 300 pixels of the viewport. The link
works when clicked too, and without JavaScript it leads to the next page.

```php
<?php
use dobron\BigPipe\MorePager;
?>
<ul class="posts"><?= renderPosts($posts) ?></ul>
<?= new MorePager('/feed?page=2', 'See more posts') ?>
```

The endpoint appends the posts and replaces the pager, the element that sent the request, with the pager of the
following page, or removes it on the last page:

```php
<?php
$response = new \dobron\BigPipe\AsyncResponse();

$response->appendContent('ul.posts', renderPosts($posts));
$response->replace('', $hasMore ? (string) new MorePager('/feed?page=' . ($page + 1)) : '');

$response->send();
```
