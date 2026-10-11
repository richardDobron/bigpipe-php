---
id: poller
title: 🔁 Poller API
sidebar_label: Poller
---

`Poller` requests a URL again and again, e.g. to check for new notifications. The browser sends the next request an
interval after the last one finished, so slow responses never pile up, and applies every response like any other.

```php
<?php
use dobron\BigPipe\Poller;

(new Poller('/notifications/poll', 30000))   // the URL and the interval in milliseconds, at least 2000
    ->setData(['since' => $lastSeen])
    ->setMaxRequests(100)                   // stops after 100 requests
    ->setMuteWhenIdle(5 * 60 * 1000)        // pauses after 5 minutes without user activity
    ->start();
```

The poller starts with the page, or with the pagelet whose content prints it. It pauses while the page is hidden,
e.g. in a background tab (`setMuteWhenHidden(false)` turns that off), and a page transition ends it
(`setClearOnQuicklingEvents(false)` keeps it going).

## The endpoint

The endpoint answers with an `AsyncResponse`. Every request of the poller sends its id, so the response can control
it:

```php
<?php
use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\Poller;

$response = new AsyncResponse();
$response->setContent('#notification-count', (string) countUnread());

if (Poller::requestedId() !== null && isQuietHour()) {
    $response->call(Poller::requestedId(), 'setInterval', [5 * 60 * 1000]); // or 'stop', 'mute'
}

$response->send();
```

## Live Example
A deploy that reports its progress until the server stops the poller, in the [demo page](http://bigpipe.etexweb.sk/tutorial/poller).
