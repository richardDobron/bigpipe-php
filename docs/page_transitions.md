---
id: page_transitions
title: 🔀 Page Transitions API
sidebar_label: Page Transitions
---

With page transitions, the links of the site load the next page into the canvas, the element with the content of the
page, instead of loading the page in full. The layout, the scripts and everything the page has loaded stay, so the
server renders only the content of the next page and its pagelets.

## Turning them on

Configure them once, e.g. when the application boots, and turn them on in the layout with the id of the canvas:

```php
<?php
use dobron\BigPipe\Quickling;

Quickling::configure(
    version: getenv('APP_VERSION'), // a page of another version is loaded in full, e.g. after a deploy
    inactivePageRegex: '^/admin',   // paths always loaded in full
    badRequestKeys: ['print'],      // query parameters that need a full load
    sessionLength: 50               // after this many page transitions the next page is loaded in full
);
?>
<header>…</header>
<main id="content"><?= $content ?></main>
<?php Quickling::init('content'); ?>
```

In a page transition `init()` does nothing, as the page in the browser has them on already, so the layout can call it
while it renders the content of a transition.

The browser part takes over the same-origin links and GET forms, and the back and forward buttons. Links handled by the
Primer (`rel="async"`, `rel="dialog"`, `ajaxify`), links with `rel="external"`, `target` or `download`, and links to
another origin are left to the browser.

## Answering a page transition

A page transition requests the same URL with the `quickling` parameter. The controller renders only the content and
answers with `transition()`: the content fills the canvas, and the pagelets printed in it are sent in the order of
their phases.

```php
<?php
use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Quickling;

$content = '<h1>Feed</h1>' . new FeedPagelet();

if (Quickling::isRequested()) {
    (new AsyncResponse())
        ->transition($content, 'Feed', 'page-feed') // the content, the title and the class of <body>
        ->send();
    return;
}

echo view('layout', ['title' => 'Feed', 'content' => $content]);
echo BigPipe::render();
```

The response carries the version of the configuration. When it differs from the version of the page in the browser,
the page is loaded in full.

A page transition accepts a [streamed response](pagelets.md#streamed-responses), so `send()` streams it: the content
reaches the browser first, and every pagelet follows as soon as it is rendered. Give the slow pagelets
[deferred content](pagelets.md#deferred-content) to profit from it.

`transitionRedirect($url)` sends the browser to another URL instead, with a page transition, or in full with
`transitionRedirect($url, force: true)`, e.g. to a login page with another layout.

## Leaving a page

The pagelets in the canvas are destroyed when the next page arrives, and the timers of the page started with the
`TimerStorage` of the browser part are cleared. See the page transitions of `bigpipe-util` for the hooks that ask
before leaving a page with unsaved changes.

## Live Example
Every page of the [demo app](http://bigpipe.etexweb.sk) is a page transition: the tutorials, the blog, the shop and the dashboard, whose pagelets are streamed in the transition too.
