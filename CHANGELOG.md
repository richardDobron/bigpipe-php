# Changelog

All notable changes to `richarddobron/bigpipe` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## Unreleased

### Added

- Laravel integration, discovered by Laravel 9 or later: `dobron\BigPipe\Laravel\BigPipeServiceProvider` binds the
  context to the request (Octane), sends the CSRF token with the `SetUpBigPipe` middleware, registers the route for a
  new token and answers an expired token so the browser sends the request again, and adds the `@bigpipe`, `@jsmod`,
  `@jsmodIf` and `@define` Blade directives. `dobron\BigPipe\Laravel\AsyncResponse` and `DialogResponse` are
  `Responsable` and streamed when the browser asks for it.
- Symfony integration (6.4 or later): `dobron\BigPipe\Symfony\BigPipeBundle` keeps a context per request (also in
  worker mode), turns the `AsyncResponse` and `DialogResponse` a controller returns into a response, sends the CSRF
  token of a configured id and adds the `bigpipe()`, `bigpipe_jsmod()` and `bigpipe_define()` Twig functions.

### Fixed

- The URL of a streamed page transition (`AsyncResponse::transition()`) no longer keeps the `__stream` parameter,
  so the browser's address bar shows the URL of the page.

## v2.0.0 - 2026-10-10

It works with `bigpipe-util` 2.x.

### Added

#### Pagelets
- Pagelet classes: the content is rendered in `content()`, the CSS and JS are declared with `$css` and `$js`, and
  the id comes from the class name, e.g. `feed` for `FeedPagelet`. `Pagelet::current()` is the pagelet being rendered.
- `defer()`: content rendered when the pagelet is rendered, not when the page is built.
- `BigPipe::stream()` sends every pagelet as soon as it is rendered, after the page printed so far.
- `BigPipe::setParallel()` renders the pagelets concurrently with Fibers (PHP 8.1): `Pagelet::await()` and
  `Pagelet::sleep()` wait without blocking the other pagelets. Also for the pagelets of an `AsyncResponse`.
- `setFallback()` and `hasFailed()`: a pagelet that throws is replaced by its fallback and the page goes on;
  `BigPipe::setErrorHandler()` reports the exception.
- `setPhase()` and `displayAfter()` decide the order in which the browser shows the pagelets;
  `BigPipe::setTtiPhase()` names the phases the user waits for and prefetches the JS of the later ones.
- `BigPipe::setPipelining(false)` renders every pagelet in its placeholder, for crawlers and browsers without JavaScript.
- Lazy pagelets: `LazyPagelet` and `Pagelet::lazy()`, loaded when visible (`LOAD_VISIBLE`), once the browser is idle
  (`LOAD_IDLE`) or right away (`LOAD_EAGER`), with a placeholder. `MorePager` loads the next page of a feed on scroll.
- `AsyncResponse::pagelet()` sends a pagelet with a response; `refreshPagelet()` renders one on the page again.
- `onLoad()`, `onAfterLoad()` and `setJSNonBlock()` for the JS files of a pagelet; `addDisplayResource()`,
  `addResource()`, `prefetch()` and `prefetchCall()` for what it needs and when.

#### Page transitions, Bootloader, Poller
- Page transitions: `Quickling::configure()`, `Quickling::init()` in the layout and `Quickling::isRequested()`;
  `AsyncResponse::transition()` answers one with the content of the page, `transitionRedirect()` sends it elsewhere.
- `Bootloader`: a resource map (`setResourceMap()`) and bootloadable modules (`enableBootload()`), loaded the first
  time they are called, preloaded while the network is idle with a priority, or with `preloadModules()`.
- `Poller`: requests a URL again and again; `Poller::requestedId()` lets the response change its interval, mute it or
  stop it.

#### Responses
- `AsyncResponse::setError()`: the browser calls the error handler of the request instead of its handler.
- `morph()` and `morphContent()` update an element and keep the focus, the caret and the values of a form.
- `define()` and `defineElement()` send data or an element that the browser requires as a module, and `instance()`
  keeps one object in the browser to call from more responses.
- Streamed responses: `AsyncResponse::isStreamRequested()` and `stream()` send the payload and the DOM operations
  first and every pagelet as soon as it is rendered.
- `AsyncResponse::output()` and `AsyncResponse::headers()`, e.g. for a framework that sends the response itself.
- Expired CSRF tokens: `BigPipe::setCSRFToken()`, with a URL that gives a new token, and
  `AsyncResponse::retryWithCSRFToken()`: the browser sends the rejected request again, once.
- `DialogResponse` setters for the options and behaviors of a dialog: `setBackdrop()`, `setKeyboard()`, `setAnimate()`,
  `setTimeout()`, `setAutoFocus()`, `setTrapFocus()`, `setRefocus()`, `setCausalElement()`, `setHideOnTransition()`,
  `setHideOnSuccess()`, `setPosition()`, `setOption()` and `setOptions()`.
- `TransportMarker::html()`, `element()`, `module()`, `map()` and `set()` as static methods.

#### Page
- `BigPipe::setNonce()`: a CSP nonce on the inline scripts, defined as the `CSPNonce` module for the stylesheets and
  scripts of the pagelets.
- `BigPipe::setScriptType('module')` renders the inline scripts as module scripts, for an entrypoint that is a module.
- `BigPipe::page()` for the modules of the page, e.g. `BigPipe::page()->call('Page', 'init')`.
- `Context` holds the pagelets and jsmods of a request; `BigPipe::setContextResolver()` and `BigPipe::withContext()`
  give long-running servers (Octane, FrankenPHP, RoadRunner, Swoole) a fresh context per request.
- `BigPipe` and `AsyncResponse` accept an optional `Context` in the constructor.
- Docblocks for the public API.

#### Demo app
- Upgraded to Laravel 13 and Vite, with a tutorial for every feature (streaming pagelets, lazy pagelets, Poller, morph,
  Bootloader, events, DOM operations, dialogs, forms, payload, transport markers, configuration, DOM references,
  expired CSRF tokens, redirects), each with a panel that shows the responses of the server, and the Laravel recipes
  as a working application. Every page is a page transition; a light and a dark theme.

### Changed
- Requires `bigpipe-util` 2.x.
- Responses are sent as `application/json` with `X-Content-Type-Options: nosniff`, instead of `text/javascript`.
- The page script defines its modules before the pagelets arrive, so their modules can require them.
- `new Pagelet()` of a pagelet class takes the id from the class; the id of a plain pagelet is still required.
- The argument of `eval()` is named `$selector`.

### Deprecated
- `require()` with a string like `"require('Module').method()"`: use `call()`.
- `eval()` and `Pagelet::addOnload()`: a Content Security Policy without `'unsafe-eval'` blocks them; call a module.
- `AsyncResponse::transport()`: the methods of `TransportMarker` are static. `transportHtml()`, `transportElement()`,
  `transportModule()`, `transportMap()` and `transportSet()`: use `html()`, `element()`, `module()`, `map()`, `set()`.
- `BigPipe::addPagelet()`: a pagelet adds itself to the page.
- `Pagelet::render()`: use `placeholder()`, the content is rendered by `content()`.

### Fixed
- `Quickling::init()` in a layout sent its call again with every page transition; it does nothing in one now.
- The state is reset even when encoding the response or rendering the pagelets throws, so it no longer leaks into
  the next request.
- PHP 8.4 deprecation of implicitly nullable parameters in `require()`.
- `$pagelet->require()` without arguments (the require proxy) threw a `TypeError`.

## v1.2.1 - 2026-10-04

### Fixed
- `AsyncResponse::send()` returns `mixed` again, as in 1.1.

## v1.2.0 - 2026-10-04

### Changed
- The modules of `bigpipe-util` are required from `bigpipe-util/dist/...` instead of `bigpipe-util/src/...`.
- The inline scripts encode their data with `JSON_THROW_ON_ERROR`.

### Added
- `BigPipe::reset()`.

## v1.1.0 - 2026-01-10

### Added
- Pagelets: `Pagelet`, `BigPipe::render()` at the end of the page, and the pagelets sent with it.
- Type annotations for the public API.

### Changed
- `setController()` takes a string or an array.

## v1.0.5 - 2025-09-07

### Added
- Require proxy support.

## v1.0.4 - 2025-01-16

### Added
- `$limit` parameter to `closeDialogs()` method.

## v1.0.3 - 2024-11-15

### Added
- Priority parameter to `require()` method.

## v1.0.2 - 2023-02-04

### Fixed
- Catch exception during string conversion

## v1.0.1 - 2022-12-23

### Added
- Module transport method `transportModule()`

## v1.0.0 - 2022-07-27

### Changed
- Set minimum required PHP version to 8.0.
- Change return value type to static.

## v0.2.0 - 2022-05-17

### Added
- Shield to prevent "JSON Hijacking"

## v0.1.4 - 2022-05-06

### Added
- Method `generate_unique_node_id` to generate unique node ID.
- Support for array in require().
- Support arguments for dialog controller.

## v0.1.3 - 2022-04-27

### Added
- Method to close only current dialog.
- Support of __toString in require arguments.

## v0.1.2 - 2022-04-14

### Added
- Dialogs support.

## v0.1.1 - 2022-03-27

### Added
- Part of BigPipe implementation for Webpack.
