---
id: laravel_recipes
title: Laravel recipes
sidebar_label: Laravel recipes
---

Worked examples for a Laravel application, a small shop with a blog, from the setup to the parts you build most:
forms with validation, dialogs, lists, a dashboard, navigation and live updates. They use the response classes of
[Laravel Integration](laravel_integration.md) (`App\Arch\BigPipe\AsyncResponse` and `DialogResponse`, whose `send()`
returns a Laravel response) and Blade.

## Set up the application

### The layout

The pages extend one layout. The content is a section, so a page transition can render just that section.

```blade title="resources/views/layouts/app.blade.php"
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="{{ $bodyClass ?? '' }}">
    <header>
        <nav>
            <a href="{{ route('posts.index') }}">Blog</a>
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('notifications.index') }}">
                Notifications <span id="unread-count">{{ auth()->user()?->unreadNotifications()->count() ?? 0 }}</span>
            </a>
        </nav>
    </header>

    <main id="content">@yield('content')</main>

    @php(\dobron\BigPipe\Quickling::init('content'))
    @unless ($partial ?? false)
        {!! \dobron\BigPipe\BigPipe::render() !!}
    </body>
    </html>
    @endunless
```

`@vite` loads the entrypoint as a deferred module, so BigPipe renders its inline script as a module script too, see
`setScriptType()` in the middleware below. With Laravel Mix or webpack the entrypoint is a classic script and nothing
needs to be set.

A page transition replaces the class of `<body>` with the one of its response (`transition($content, $title, $bodyClass)`).
Share the class of your layout, e.g. `View::share('bodyClass', 'bg-gray-50')` in a service provider, print it in the
layout and give it to `transition()`, or the page loses its classes after the first transition.

`$partial` is for a view that is rendered for its content only: a page transition, or a streamed page. The layout then
leaves out the script of BigPipe and the closing tags. This matters: rendering a view runs its layout too, and
`BigPipe::render()` would take the pagelets and the modules of the content before they are in the response.

### The entrypoint

```javascript title="resources/js/app.js"
import Primer from 'bigpipe-util/dist/Primer';
import AsyncRequest from 'bigpipe-util/dist/async/AsyncRequest';
import { setModuleLoader } from 'bigpipe-util/dist/ModuleRegistry';

Primer();

// Eager, because the server calls modules synchronously. The entrypoint itself is left out.
const modules = import.meta.glob(['./**/*.js', '!./app.js'], { eager: true });
setModuleLoader(name => modules[`./${name}.js`]?.default);

// Errors of the links and forms of the Primer: show the message the server gave.
AsyncRequest.setDefaultErrorHandler((xhr, error) => {
    alert(error.summary + (error.description ? '\n' + error.description : ''));
});
```

Replace the `alert()` with the toast or dialog of your design. `error.summary` and `error.description` are what the
server gave to `setError()`; for a failed connection or an HTTP error the library provides a message of its own.

### A middleware for the state of the request

Everything BigPipe needs to know about the request, in one place, in the `web` group after the session starts:

```php title="app/Http/Middleware/SetUpBigPipe.php"
<?php

namespace App\Http\Middleware;

use Closure;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Quickling;
use Illuminate\Http\Request;

class SetUpBigPipe
{
    public function handle(Request $request, Closure $next)
    {
        // Expired tokens: the browser gets a new one from this URL and sends the request again.
        BigPipe::setCSRFToken(csrf_token(), refreshUri: route('csrf-token', absolute: false));

        BigPipe::setScriptType('module');                 // Vite, see the layout
        BigPipe::setNonce($request->attributes->get('csp_nonce')); // from your CSP middleware, or null
        BigPipe::setTtiPhase(0);                          // the first phase is what the user waits for
        BigPipe::setPipelining(! $this->isCrawler($request));

        Quickling::configure(
            version: config('app.version'),               // a deploy loads pages in full once
            inactivePageRegex: '^/(admin|login|logout)',  // these paths are never loaded into the canvas
            badRequestKeys: ['print']
        );

        return $next($request);
    }

    private function isCrawler(Request $request): bool
    {
        return (bool) preg_match('/bot|crawl|spider|slurp|preview/i', (string) $request->userAgent());
    }
}
```

```php title="routes/web.php"
Route::get('/csrf-token', function () {
    \dobron\BigPipe\BigPipe::setCSRFToken(csrf_token(), refreshUri: route('csrf-token', absolute: false));

    return (new \App\Arch\BigPipe\AsyncResponse())->send();
})->name('csrf-token');
```

### Validation and expired sessions, for every request

Answer the validation errors of a request made by BigPipe with the messages in place, and an expired token with a
response that sends the request again. Both are in the exception handler, so no controller repeats them:

```php title="app/Exceptions/Handler.php"
use App\Arch\BigPipe\AsyncResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

public function register(): void
{
    $this->renderable(function (ValidationException $e, $request) {
        if (! $request->ajax()) {
            return null;
        }

        $response = new AsyncResponse();
        $selector = fn (string $field) => '#error-' . str_replace('.', '-', $field);

        // <p id="error-title" class="error"></p> next to every field: clear the old messages, then show the new.
        foreach (array_keys($request->except('_token')) as $field) {
            $response->setContent($selector($field), '');
        }
        foreach ($e->errors() as $field => $messages) {
            $response->setContent($selector($field), e($messages[0]));
        }

        // Status 200 on purpose: an HTTP error would skip the DOM operations above.
        return $response
            ->setError('Please check the form', 'Some fields need your attention.', code: 422)
            ->send();
    });

    // Laravel turns a TokenMismatchException into an HttpException with the status 419.
    $this->renderable(function (HttpException $e, $request) {
        if ($e->getStatusCode() === 419 && $request->ajax()) {
            return (new AsyncResponse())->retryWithCSRFToken(csrf_token())->send();
        }
    });
}
```

Fields with no element for their message are skipped silently. The default error handler of the entrypoint shows
`Please check the form`, and the messages are next to the fields.

## A comment form

A form with `rel="async"` is sent in the background. The controller adds the comment to the list, clears the message and
asks a module the application owns to reset the form:

```blade title="resources/views/posts/_comment-form.blade.php"
<form id="comment-form" action="{{ route('comments.store', $post) }}" method="POST" rel="async">
    @csrf
    <textarea name="body" rows="3"></textarea>
    <p id="error-body" class="error"></p>

    <button type="submit">Comment</button>
    <span class="form-loader">Sending...</span>
</form>

<ul id="comments">
    @each('posts._comment', $post->comments, 'comment')
</ul>
```

```php title="app/Http/Controllers/CommentController.php"
<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use App\Models\Post;
use dobron\BigPipe\TransportMarker;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Post $post)
    {
        $request->validate(['body' => 'required|string|max:2000']); // errors: see the exception handler

        $comment = $post->comments()->create([
            'body' => $request->input('body'),
            'user_id' => $request->user()->id,
        ]);

        return (new AsyncResponse())
            ->prependContent('#comments', view('posts._comment', compact('comment'))->render())
            ->setContent('#error-body', '')
            ->call('Form/Reset', null, [TransportMarker::element('comment-form')])
            ->send();
    }
}
```

```javascript title="resources/js/Form/Reset.js"
export default function Reset(form) {
    form.reset();
    form.querySelector('textarea, input')?.focus();
}
```

The `.form-loader` element gets the class `loading` while the request is running, and the inputs are read-only, so a
second click does not send a second comment.

### Warn before leaving a form with unsaved changes

Start a [FormMonitor](https://github.com/richardDobron/bigpipe-util/blob/main/docs/form_monitor.md) from the page, and the
user is asked before a link, a page transition or a closed tab throws away what they typed. It becomes clean when the
request succeeds:

```php
// in the controller that renders the edit page
\dobron\BigPipe\BigPipe::page()->call('bigpipe-util/dist/core/FormMonitor', null, [
    \dobron\BigPipe\TransportMarker::element('post-form'),
    ['message' => 'Leave without saving the post?'],
]);
```

## Cart: links that change several parts of the page

```blade title="resources/views/cart/_line.blade.php"
<li id="line-{{ $line->id }}">
    {{ $line->product->name }} × {{ $line->quantity }}
    <a href="{{ route('cart.show') }}" ajaxify="{{ route('cart.remove', $line) }}" rel="async-post">Remove</a>
</li>
```

`rel="async-post"` sends a `POST` to the `ajaxify` URL. A link with a real `href` still works for a middle click and without
JavaScript. One response updates the line, the total and the badge in the header:

```php title="app/Http/Controllers/CartController.php"
public function remove(Request $request, CartLine $line)
{
    $this->authorize('update', $line->cart);
    $line->delete();

    $cart = $line->cart->fresh();

    return (new AsyncResponse())
        ->remove('#line-' . $line->id)
        ->setContent('#cart-total', number_format($cart->total(), 2))
        ->setContent('#cart-badge', (string) $cart->lines()->count())
        ->setContent('#cart-empty', $cart->lines()->exists() ? '' : 'Your cart is empty.')
        ->send();
}
```

Use `morph()` instead of `setContent()` to update a part the user may be working in, such as a quantity field: it keeps the
focus and the typed value of the elements that stay.

```php
->morph('#cart-lines', view('cart._lines', compact('cart'))->render())
```

## A confirmation dialog

A link opens the dialog, and the dialog sends the delete. When the request succeeds the dialog closes itself and the row
is removed:

```blade
<a href="{{ route('posts.edit', $post) }}" ajaxify="{{ route('posts.delete-dialog', $post) }}" rel="dialog">Delete</a>
```

A `rel="dialog"` link sends a `POST`, so the route that opens the dialog is a `POST` route:

```php title="app/Http/Controllers/PostController.php"
public function deleteDialog(Post $post)
{
    $this->authorize('delete', $post);

    return (new DialogResponse())
        ->setTitle('Delete "' . e($post->title) . '"?')
        ->setBody('<p>The post and its comments will be removed.</p>')
        ->setFooter(
            '<form action="' . route('posts.destroy', $post) . '" method="POST" rel="async">'
            . csrf_field() . method_field('DELETE')
            . '<button type="button" data-dismiss="modal">Cancel</button> '
            . '<button type="submit">Delete</button>'
            . '</form>'
        )
        ->setBackdrop('static')             // a click next to the dialog does not close it
        ->setHideOnSuccess('form')          // it closes when the form's request succeeds
        ->setPosition(80)
        ->dialog()
        ->send();
}

public function destroy(Post $post)
{
    $this->authorize('delete', $post);
    $post->delete();

    return (new AsyncResponse())
        ->remove('#post-' . $post->id)
        ->send();
}
```

Focus moves into the dialog, <kbd>Tab</kbd> stays inside, <kbd>Esc</kbd> closes it, and the focus returns to the link that
opened it. See [Dialogs](dialogs.md) for all options.

## An infinite feed

`MorePager` prints the link to the next page and loads it when the user scrolls near it. The endpoint appends the next
posts and replaces the link with the one for the page after, or removes it on the last page:

```blade title="resources/views/posts/index.blade.php"
@extends('layouts.app')

@section('content')
    <ul id="posts">@include('posts._items', ['posts' => $posts])</ul>

    @if ($posts->hasMorePages())
        {!! new \dobron\BigPipe\MorePager($posts->nextPageUrl(), 'Load more posts') !!}
    @endif
@endsection
```

```php title="app/Http/Controllers/PostController.php"
use dobron\BigPipe\MorePager;
use dobron\BigPipe\Quickling;

public function index(Request $request)
{
    $posts = Post::with('author')->latest()->simplePaginate(10);

    // The link of the pager, not a page transition, which is also an AJAX request.
    if ($request->ajax() && ! Quickling::isRequested()) {
        return (new AsyncResponse())
            ->appendContent('#posts', view('posts._items', compact('posts'))->render())
            ->replace('', $posts->hasMorePages()
                ? (string) new MorePager($posts->nextPageUrl(), 'Load more posts')
                : '')
            ->send();
    }

    return $this->page('posts.index', compact('posts'), 'Blog');
}
```

`replace('')` has no selector, so it replaces the element that sent the request, the link. Without JavaScript the link is a
normal link to the next page. `page()` is the helper of [Page transitions](#page-transitions).

## A dashboard of pagelets

A dashboard is made of independent parts that load at different speeds. Each is a pagelet; the page is sent at once, and
every pagelet follows as soon as it is ready.

```php title="app/Pagelets/RevenuePagelet.php"
<?php

namespace App\Pagelets;

use App\Models\Order;
use dobron\BigPipe\Pagelet;

class RevenuePagelet extends Pagelet
{
    protected array $css = ['/build/revenue.css'];
    protected mixed $fallback = '<p class="muted">Revenue is not available right now.</p>';

    protected function content(): string
    {
        $this->call('Dashboard/Chart', 'draw', ['revenue']);          // runs when the pagelet is displayed

        return view('dashboard._revenue', [
            'total' => Order::whereDate('created_at', today())->sum('total'),
        ])->render();
    }
}
```

```php title="app/Pagelets/ActivityPagelet.php"
class ActivityPagelet extends Pagelet
{
    protected int $phase = 1;          // after the first phase is displayed

    protected function content(): string
    {
        return view('dashboard._activity', ['events' => ActivityLog::latest()->limit(20)->get()])->render();
    }
}
```

```blade title="resources/views/dashboard.blade.php"
@extends('layouts.app')

@section('content')
    <section class="grid">
        {!! new \App\Pagelets\RevenuePagelet() !!}
        {!! new \App\Pagelets\ActivityPagelet() !!}
        {!! \App\Pagelets\ReportPagelet::lazy(route('dashboard.report'), placeholder: '<p>Loading the report...</p>') !!}
    </section>
@endsection
```

- A failing pagelet shows its `$fallback`, reports the exception (see `BigPipe::setErrorHandler()`) and the dashboard
  stays up.
- `ActivityPagelet` is in phase 1: it is displayed after the revenue, and with `setTtiPhase(0)` the browser downloads its
  scripts in the background once phase 0 is displayed.
- `ReportPagelet` is another pagelet class. `lazy()` leaves a placeholder and loads the pagelet from its URL when it
  becomes visible. The endpoint answers with `(new AsyncResponse())->pagelet(new ReportPagelet())->send()`.

### Streaming the dashboard

`render()` waits until the slowest pagelet is rendered. With `stream()` the page is flushed first and every pagelet is
sent as soon as it is rendered. Render the view as a partial, which makes the layout leave out the script and the closing
tags, and return a streamed response. A page transition to the dashboard streams its pagelets the same way:

```php title="app/Http/Controllers/DashboardController.php"
use App\Arch\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Quickling;

public function __invoke()
{
    if (Quickling::isRequested()) {
        $content = view('dashboard', ['partial' => true])->renderSections()['content'];
        $response = (new AsyncResponse())->transition($content, 'Dashboard');

        return AsyncResponse::isStreamRequested()
            ? response()->stream(fn () => $response->stream(), 200, AsyncResponse::headers() + ['X-Accel-Buffering' => 'no'])
            : $response->send();
    }

    return response()->stream(function () {
        echo view('dashboard', ['partial' => true])->render();   // the page with the pagelet placeholders
        BigPipe::stream();                                        // the pagelets, one by one
        echo '</body></html>';
    }, 200, ['Content-Type' => 'text/html; charset=utf-8', 'X-Accel-Buffering' => 'no']);
}
```

Turn off output buffering of the proxy too, see [Streaming](pagelets.md#streaming) and
[Streamed responses](pagelets.md#streamed-responses).

### Rendering the slow pagelets at the same time

If a pagelet waits for an API, start the request without blocking and `await` it. With `setParallel(true)` the other
pagelets are rendered meanwhile, and the dashboard takes as long as its slowest part instead of the sum:

```php
BigPipe::setParallel(true);   // PHP 8.1+
```

```php
protected function content(): string
{
    $handle = curl_init('https://api.example.com/rates');
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    $multi = curl_multi_init();
    curl_multi_add_handle($multi, $handle);

    // Polled while the other pagelets are rendered, until it returns something else than null.
    $body = Pagelet::await(function () use ($multi, $handle) {
        curl_multi_exec($multi, $running);

        return $running ? null : curl_multi_getcontent($handle);
    }, timeout: 5.0);

    return view('dashboard._rates', ['rates' => json_decode($body, true)])->render();
}
```

See [Parallel rendering](pagelets.md#parallel-rendering) for what can wait and the restrictions. Laravel's `Http` client
blocks, so it does not let the others go on; use `curl_multi` or an async driver for the part that waits.

## Page transitions

With `Quickling::init('content')` in the layout, a click on a normal link loads only the content of the next page. The
same controller answers both kinds of request with one helper, which renders the `content` section of the view for a
page transition and the whole page otherwise:

```php title="app/Http/Controllers/Controller.php"
use App\Arch\BigPipe\AsyncResponse;
use dobron\BigPipe\Quickling;

abstract class Controller extends BaseController
{
    protected function page(string $view, array $data = [], ?string $title = null)
    {
        $title ??= config('app.name');
        $transition = Quickling::isRequested();

        // A partial layout leaves out the script of BigPipe, which would take the pagelets and the
        // modules of the content before they are in the response.
        $page = view($view, $data + ['title' => $title, 'partial' => $transition]);

        if ($transition) {
            // Only the content: the layout, the scripts and what the page has loaded stay.
            return (new AsyncResponse())
                ->transition($page->renderSections()['content'], $title, view()->shared('bodyClass', ''))
                ->send();
        }

        return $page;
    }
}
```

```php
public function show(Post $post)
{
    return $this->page('posts.show', compact('post'), $post->title);
}
```

A page whose layout differs, such as the login, answers with `->transitionRedirect(route('login'), force: true)` to be loaded
in full. The pagelets of the content are sent by the same response, so the dashboard works the same way.

Run code when a page is left, such as stopping a timer, with `Run.onLeave()`; see [page transitions](page_transitions.md).

## Live notifications

A `Poller` asks for the notifications every 30 seconds while the tab is visible, and the server controls the pace:

```blade title="resources/views/notifications/index.blade.php"
@extends('layouts.app')

@section('content')
    <ul id="notifications">@include('notifications._items', ['items' => $items])</ul>

    @php
        (new \dobron\BigPipe\Poller(route('notifications.poll'), 30000))
            ->setMuteWhenIdle(5 * 60 * 1000)   // pauses after 5 minutes without any activity
            ->start();
    @endphp
@endsection
```

```php title="app/Http/Controllers/NotificationController.php"
use dobron\BigPipe\Poller;

public function poll(Request $request)
{
    $unread = $request->user()->unreadNotifications();

    $response = (new AsyncResponse())
        ->setContent('#unread-count', (string) $unread->count())
        ->setContent('#notifications', view('notifications._items', ['items' => $unread->get()])->render());

    if (Poller::requestedId() !== null && ! $unread->exists()) {
        $response->call(Poller::requestedId(), 'setInterval', [120000]);    // quiet: ask less often
    }

    return $response->send();
}
```

Every poll replaces the list, so a poll that arrives twice does no harm. Page transitions stop the poller of the page that
is left, unless you call `setClearOnQuicklingEvents(false)`.

## Uploading a file

A form with `rel="async"` and a chosen file is sent as `multipart/form-data` and reports the progress:

```blade
<form action="{{ route('avatar.update') }}" method="POST" rel="async" enctype="multipart/form-data">
    @csrf
    <input type="file" name="avatar" accept="image/*">
    <progress value="0" max="100"></progress>
    <p id="error-avatar" class="error"></p>
    <button type="submit">Upload</button>
</form>
<img id="avatar" src="{{ $user->avatarUrl() }}" width="96" height="96" alt="">
```

```php title="app/Http/Controllers/AvatarController.php"
public function update(Request $request)
{
    $request->validate(['avatar' => 'required|image|max:2048']);   // errors: see the exception handler

    $path = $request->file('avatar')->store('avatars', 'public');
    $request->user()->update(['avatar' => $path]);

    return (new AsyncResponse())
        ->call('Avatar/Swap', null, ['#avatar', Storage::url($path)])
        ->setContent('#error-avatar', '')
        ->send();
}
```

```javascript title="resources/js/Avatar/Swap.js"
export default function Swap(selector, url) {
    document.querySelector(selector).src = url + '?t=' + Date.now();
}
```

The `<progress>` element gets its `value` and `max` from the upload, the form informs `uploadprogress` for your own code, and
the CSS variable `--upload-progress` is available to style a bar.

## Testing

A response is JSON behind the `for (;;);` shield. Strip it, and assert on the DOM operations and modules.
`Quickling::isRequested()` and `Poller::requestedId()` read the superglobals, which the test client does not fill: set
`$_GET` and `$_REQUEST` from the query string of the request in a helper, as the
[demo application](https://github.com/richardDobron/bigpipe-php/tree/main/demo-app) does in `tests/Feature/ShopTest.php`.

```php title="tests/Feature/CommentTest.php"
private function bigpipe($response): array
{
    return json_decode(substr($response->getContent(), strlen('for (;;);')), true);
}

public function test_a_comment_is_prepended_to_the_list(): void
{
    $post = Post::factory()->create();

    $response = $this->actingAs(User::factory()->create())
        ->postJson(route('comments.store', $post), ['body' => 'Nice!'], ['X-Requested-With' => 'XMLHttpRequest']);

    $data = $this->bigpipe($response);

    $this->assertSame('prependContent', $data['domops'][0][0]);
    $this->assertSame('#comments', $data['domops'][0][1]);
    $this->assertStringContainsString('Nice!', $data['domops'][0][3]['__html']);
}

public function test_an_empty_comment_is_rejected_with_a_message_at_the_field(): void
{
    $response = $this->actingAs(User::factory()->create())
        ->postJson(route('comments.store', Post::factory()->create()), ['body' => ''], ['X-Requested-With' => 'XMLHttpRequest']);

    $data = $this->bigpipe($response);

    $this->assertSame(422, $data['error']);
    $this->assertContains(
        ['setContent', '#error-body', false, ['__html' => 'The body field is required.']],
        $data['domops']
    );
}
```

## Octane and other long-running servers

Every example above uses the request-scoped state of BigPipe. Bind the context to the request scope once, see
[Long-running servers](laravel_integration.md#long-running-servers-octane-frankenphp-roadrunner-swoole). Do not read `$_GET`
and `$_REQUEST` yourself: `Quickling::isRequested()` and `Poller::requestedId()` do, so use them only where the server
fills the superglobals (PHP-FPM, FrankenPHP in classic mode).
