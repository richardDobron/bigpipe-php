---
id: example_page
title: A pipelined page
sidebar_label: Pipelined page
---

A news page with an article, a slow feed and a sidebar that loads when the user scrolls to it. The article reaches the
browser first, the feed follows as soon as it is rendered, and the sidebar is requested later.

## The pagelets

```php title="app/Pagelets/FeedPagelet.php"
<?php
use dobron\BigPipe\Pagelet;

class FeedPagelet extends Pagelet
{
    protected array $css = ['/css/feed.css'];
    protected mixed $fallback = '<p>The feed is not available right now.</p>';

    protected function content(): string
    {
        $this->call('Feed', 'init');       // a module of the page bundle

        return renderFeed(loadPosts());    // e.g. slow queries
    }
}

class SidebarPagelet extends Pagelet
{
    protected function content(): string
    {
        return renderSidebar();
    }
}
```

`content()` runs when the pagelet is rendered, so a streamed page is sent before the feed is ready. If it throws, the
page keeps working and shows the fallback.

## The page

```php title="page.php"
<?php
use dobron\BigPipe\BigPipe;
?>
<!DOCTYPE html>
<html>
<head>
    <title>News</title>
    <script src="/js/app.js"></script>
</head>
<body>
    <article><?= renderArticle() ?></article>

    <main><?= new FeedPagelet() ?></main>
    <aside><?= SidebarPagelet::lazy('/pagelets/sidebar', [], '<p>Loading…</p>') ?></aside>

    <?php BigPipe::stream(); ?>
</body>
</html>
```

`BigPipe::stream()` flushes the page printed so far, then the feed in its own `<script>`. Use
`<?= BigPipe::render() ?>` instead to send all pagelets at the end of the request. The sidebar is only a placeholder
until it is visible, then the browser requests the endpoint.

## The endpoint

```php title="/pagelets/sidebar"
<?php
(new \dobron\BigPipe\AsyncResponse())
    ->pagelet(new SidebarPagelet())
    ->send();
```

The response puts the pagelet in the place of the placeholder and runs its modules like on a page.

## The browser

```javascript title="resources/js/Feed.js"
export default class Feed {
    init() {
        console.log('The feed is displayed.');
    }
}
```

See [Pagelets](pagelets.md) for streaming, display order and fallbacks, and [Lazy pagelets](lazy_pagelets.md) for the
loading options.
