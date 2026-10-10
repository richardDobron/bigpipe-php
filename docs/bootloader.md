---
id: bootloader
title: 📦 Bootloader API
sidebar_label: Bootloader
---

`Bootloader` names the static files of the application and the JavaScript modules the browser loads only when the
server calls them. The browser part loads every file once and skips the stylesheets and scripts already on the page.

## Resource map

Name the files once, e.g. from the manifest of the bundler, and use the names in place of URLs:

```php
<?php
use dobron\BigPipe\Bootloader;
use dobron\BigPipe\Pagelet;

Bootloader::setResourceMap([
    'feed.css' => ['type' => 'css', 'src' => '/static/feed.1a2b3c.css'],
    'feed.js' => ['type' => 'js', 'src' => '/static/feed.4d5e6f.js'],
]);

$feed = (new Pagelet('feed'))
    ->addCss('feed.css')
    ->addJs('feed.js');
```

A pagelet or a response sends only the part of the map it uses.

## Bootloadable modules

A module that the page rarely needs, like an editor, does not have to be in the bundle of the page. List the resources
it needs, and call it like any other module:

```php
<?php
use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\Bootloader;
use dobron\BigPipe\TransportMarker;

Bootloader::setResourceMap([
    'editor.css' => ['type' => 'css', 'src' => '/static/editor.css'],
    'editor.js' => ['type' => 'js', 'src' => '/static/editor.js'],
]);
Bootloader::enableBootload(['Editor' => ['editor.css', 'editor.js']]);

(new AsyncResponse())
    ->call('Editor', 'open', [TransportMarker::element('post')])
    ->send();
```

The page, a pagelet or a response that calls the module sends its resources with it, and the browser loads them
before it calls the module. The script has to make the module available when it runs, e.g. a chunk of the bundle that
calls `registerModules()` from `bigpipe-util/dist/ModuleRegistry`.

## Display resources

A pagelet waits for its CSS before it is displayed, and for its JS before it runs its `onLoad()` modules. With the
resource map it can split its resources further:

```php
<?php
$feed = (new Pagelet('feed'))
    ->addDisplayResource('feed.css')           // needed to display the pagelet
    ->addResource('dialog.css', 'feed.js');    // needed only before its onLoad() modules run
```

Resources added with `addResource()` load with the JavaScript of the pagelets, so a stylesheet that is not needed to
show the pagelet, e.g. of a dialog it opens, does not delay its display.

## Preloading and prefetching

With a priority above 0, every page preloads the resources of bootloadable modules while the network is idle, higher
priorities first, so they load fast when they are called:

```php
<?php
Bootloader::enableBootload(['Dialog' => ['dialog.css', 'dialog.js']], priority: 2);
```

`Bootloader::preloadModules('Editor')` preloads the resources of modules on the current page right away.

A pagelet can prefetch what the user is likely to need next: the resources load as soon as the pagelet arrives, and
the module calls run once they are loaded.

```php
<?php
$feed = (new Pagelet('feed'))
    ->prefetch('composer.js')
    ->prefetchCall('Composer', 'warmUp');
```

## Live Example
An editor that is not in the bundle, loaded with its CSS the first time the server calls it, in the [demo page](http://bigpipe.etexweb.sk/tutorial/bootloader).
