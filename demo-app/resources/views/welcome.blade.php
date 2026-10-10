@extends('layouts.base')

@section('title', 'BigPipe: pipelining web pages')

@section('canvas')
    <div class="hero-wrap">
        <section class="page hero" style="padding-top:88px;padding-bottom:88px">
            <div>
                <a href="https://richarddobron.github.io/bigpipe-php/docs/pagelets" class="badge"><b>PHP 8 · Laravel 13</b> Pagelets render in parallel →</a>
                <h1 class="hero-title" style="margin-top:24px">Pages that arrive<br><span class="grad-text">in pieces.</span></h1>
                <p class="lead" style="margin-top:22px">
                    BigPipe sends the page at once and every part of it as soon as it is ready. Update the DOM, open dialogs
                    and call JavaScript modules from PHP: an application that feels fast, without becoming a single-page app.
                </p>
                <div style="margin-top:32px;display:flex;gap:10px;flex-wrap:wrap">
                    <a href="/app/dashboard" class="btn">See it stream <span class="arrow">→</span></a>
                    <a href="https://richarddobron.github.io/bigpipe-php/docs/getting_started" class="btn ghost">Get started</a>
                </div>
                <div class="install" style="margin-top:20px">
                    <div><span>$</span>composer require richarddobron/bigpipe<em>PHP</em></div>
                    <div><span>$</span>npm install bigpipe-util<em>browser</em></div>
                </div>
            </div>

            <div class="demo play" id="demo" aria-label="A page whose parts arrive as they are ready">
                <div class="top">
                    <i></i><i></i><i></i>
                    <span class="url">demo.test/app/dashboard</span>
                    <span class="clock" id="clock">0 ms</span>
                    <button type="button" class="replay" id="replay">Replay</button>
                </div>
                <div class="slots">
                    <div class="slot s-head"><div class="fill" style="--t:30"><span class="ln"></span><span class="ln"></span><span class="ln" style="width:44px"></span></div></div>
                    <div class="slot s-side"><div class="fill" style="--t:30"><span class="ln" style="width:70%"></span><span class="ln"></span><span class="ln" style="width:80%"></span><span class="ln" style="width:60%"></span></div></div>
                    <div class="slot s-main"><div class="fill" style="--t:600;--c:var(--blue)"><span class="tag">RevenuePagelet</span><div class="bars"><i style="height:40%"></i><i style="height:60%"></i><i style="height:30%"></i><i style="height:80%"></i><i style="height:100%"></i><i style="height:72%"></i><i style="height:90%"></i></div></div></div>
                    <div class="slot s-c1"><div class="fill" style="--t:900;--c:var(--violet)"><span class="tag">ActivityPagelet</span><span class="ln"></span><span class="ln" style="width:70%"></span></div></div>
                    <div class="slot s-c2"><div class="fill" style="--t:1200;--c:var(--magenta)"><span class="tag">ReportPagelet</span><span class="ln"></span><span class="ln" style="width:55%"></span></div></div>
                </div>
                <div class="waterfall" style="--max:1250">
                    <div class="wf-row" style="--c:var(--ink);--t:30"><span class="name">layout</span><span class="track"><span class="seg"></span></span><span class="ms">30 ms</span></div>
                    <div class="wf-row" style="--c:var(--blue);--t:600"><span class="name">revenue</span><span class="track"><span class="seg"></span></span><span class="ms">600 ms</span></div>
                    <div class="wf-row" style="--c:var(--violet);--t:900"><span class="name">activity</span><span class="track"><span class="seg"></span></span><span class="ms">900 ms</span></div>
                    <div class="wf-row" style="--c:var(--magenta);--t:1200"><span class="name">report</span><span class="track"><span class="seg"></span></span><span class="ms">1200 ms</span></div>
                </div>
            </div>
        </section>
    </div>

    <section id="how" class="page">
        <div class="eyebrow"><span class="label">How it works</span></div>
        <h2 class="section-title">Stop waiting for the slowest query to show anything.</h2>
        <p class="lead" style="margin-top:14px">
            The dashboard of this demo has three parts that call slow APIs. Rendered the usual way, the browser gets nothing until all of them are done.
            With BigPipe the layout is flushed first and the parts are rendered at the same time.
        </p>

        <div class="compare">
            <div class="panel">
                <div class="head"><h3>A regular response</h3><span class="label">one after another</span></div>
                <div style="--max:2750">
                    <div class="wf-row" style="--c:var(--faint);--t:600"><span class="name">revenue</span><span class="track"><span class="seg"></span></span><span class="ms">600 ms</span></div>
                    <div class="wf-row" style="--c:var(--faint);--s:600;--t:900"><span class="name">activity</span><span class="track"><span class="seg"></span></span><span class="ms">900 ms</span></div>
                    <div class="wf-row" style="--c:var(--faint);--s:1500;--t:1200"><span class="name">report</span><span class="track"><span class="seg"></span></span><span class="ms">1200 ms</span></div>
                    <div class="wf-row" style="--c:var(--ink);--s:2700;--t:40"><span class="name">layout</span><span class="track"><span class="seg"></span></span><span class="ms">2.7 s</span></div>
                </div>
                <div class="result">
                    <div><strong>2.7 s</strong><span>until the first pixel</span></div>
                    <div><strong>2.7 s</strong><span>until it is complete</span></div>
                </div>
            </div>
            <div class="panel featured">
                <div class="head"><h3>With BigPipe</h3><span class="label" style="color:var(--violet)">streamed, in parallel</span></div>
                <div style="--max:2750">
                    <div class="wf-row" style="--c:var(--ink);--t:40"><span class="name">layout</span><span class="track"><span class="seg"></span></span><span class="ms">first</span></div>
                    <div class="wf-row" style="--c:var(--blue);--t:600"><span class="name">revenue</span><span class="track"><span class="seg"></span></span><span class="ms">600 ms</span></div>
                    <div class="wf-row" style="--c:var(--violet);--t:900"><span class="name">activity</span><span class="track"><span class="seg"></span></span><span class="ms">900 ms</span></div>
                    <div class="wf-row" style="--c:var(--magenta);--t:1200"><span class="name">report</span><span class="track"><span class="seg"></span></span><span class="ms">1200 ms</span></div>
                </div>
                <div class="result">
                    <div><strong class="grad-text">At once</strong><span>the layout and placeholders</span></div>
                    <div><strong>1.2 s</strong><span>as long as the slowest part</span></div>
                </div>
            </div>
        </div>

        <div class="features">
            <div class="code-window">
                <div class="chrome"><i></i><i></i><i></i><span>app/Pagelets/RevenuePagelet.php</span></div>
<pre style="margin:0;padding:18px 20px 22px;overflow:auto;font-size:13px;line-height:1.7"><code class="language-php">class RevenuePagelet extends Pagelet
{
    protected mixed $fallback = 'Revenue is not available right now.';

    protected function content(): string
    {
        // A slow API: waiting here does not hold up the other pagelets.
        $orders = $this->api->orders();

        // Call a JavaScript module once the pagelet is on the page.
        $this->call('Dashboard/Chart', 'draw', [$orders->daily()]);

        return view('dashboard._revenue', ['total' => $orders->total()])->render();
    }
}</code></pre>
            </div>
            <div class="feature-list">
                <div class="feature"><h3>Pagelets</h3><p>Independent parts with their own CSS, JavaScript and a fallback when they fail.</p></div>
                <div class="feature"><h3>Lazy pagelets</h3><p>Load a part when it becomes visible, and infinite scroll.</p></div>
                <div class="feature"><h3>Async responses</h3><p>Links and forms that set, append, replace or remove content by a selector.</p></div>
                <div class="feature"><h3>Dialogs</h3><p>Open, stack and close dialogs from the server.</p></div>
                <div class="feature"><h3>Page transitions</h3><p>Load the next page into the layout instead of in full.</p></div>
                <div class="feature"><h3>Poller &amp; bootloader</h3><p>Polling the server controls, and modules loaded only when called.</p></div>
            </div>
        </div>
    </section>

    <section id="tutorials" class="page" style="padding-top:32px">
        <div class="eyebrow"><span class="label">Tutorials</span></div>
        <h2 class="section-title">Learn it one feature at a time.</h2>
        <p class="lead" style="margin-top:14px">
            Every tutorial is a working example with its code, and a panel that shows what the server sent back.
            Page transitions, infinite scroll and uploads are in the demo app below.
        </p>

        <div class="index-grid" style="margin-top:32px">
            @foreach ([
                ['Streaming pagelets', '/tutorial/pagelets', 'Parts of a page rendered in parallel and flushed as they are ready, with fallbacks, phases and a refresh.'],
                ['Lazy pagelets', '/tutorial/lazy-pagelets', 'A pagelet loaded when it scrolls into view, or once the browser is idle.'],
                ['Poller', '/tutorial/poller', 'A deploy that reports its progress; the server stops the poller when it is done.'],
                ['Morph', '/tutorial/morph', 'A form rendered on the server with every keystroke, and the focus stays where it was.'],
                ['Bootloader', '/tutorial/bootloader', 'A module that is not in the bundle, loaded with its CSS the first time the server calls it.'],
                ['Events', '/tutorial/events', 'One event informed from PHP, and independent widgets that react to it.'],
                ['Dialogs', '/tutorial/dialogs', 'Open dialogs from the server, stack several of them and close them all at once.'],
                ['Basic usages', '/tutorial/basic-example', 'The DOM operations: set, append, prepend, replace and remove content by a selector.'],
                ['Forms', '/tutorial/forms', 'A form that is sent in the background, and its validation errors.'],
                ['Payload', '/tutorial/payload', 'Data for your JavaScript next to the DOM operations: a live username check.'],
                ['Transport markers', '/tutorial/transport-markers', 'Send HTML, but also Map, Set and Element values to a module.'],
                ['Custom configuration', '/tutorial/configuration', 'Define the configuration of the page as a module and change it with any response.'],
                ['DOM references', '/tutorial/dom-references', 'Define an element of the page as a module that any module can require by name.'],
                ['Expired CSRF tokens', '/tutorial/csrf', 'The browser gets a new token and sends the request again.'],
                ['Refresh & redirecting', '/tutorial/redirecting', 'Reload the page or redirect, with a delay.'],
            ] as $i => [$name, $href, $text])
                <a href="{{ $href }}" class="index-card">
                    <span class="num">{{ sprintf('%02d', $i + 1) }}</span>
                    <span class="go">→</span>
                    <h3>{{ $name }}</h3>
                    <p>{{ $text }}</p>
                </a>
            @endforeach
        </div>
    </section>

    <section id="shop" class="page" style="padding-top:32px">
        <div class="eyebrow"><span class="label">Demo app</span></div>
        <h2 class="section-title">Recipes for a real application.</h2>
        <p class="lead" style="margin-top:14px">
            A blog and a shop built the way an application is, with a database, validation, tests and expired sessions.
            Every page is a page transition.
        </p>

        <div class="index-grid" style="margin-top:32px">
            @foreach ([
                ['Blog', '/app/posts', 'An infinite feed, comments with validation errors and a confirmation dialog.'],
                ['Shop and cart', '/app/shop', 'Links that change several parts of the page from one response.'],
                ['Dashboard', '/app/dashboard', 'Pagelets that are streamed as they are ready, rendered at the same time, one of them lazy.'],
                ['Notifications', '/app/notifications', 'A poller that the server controls.'],
                ['Post editor', '/app/posts/1/edit', 'A warning before leaving a form with unsaved changes.'],
                ['Profile', '/app/profile', 'A file upload with a progress bar.'],
            ] as $i => [$name, $href, $text])
                <a href="{{ $href }}" class="index-card">
                    <span class="num">{{ sprintf('%02d', $i + 1) }}</span>
                    <span class="go">→</span>
                    <h3>{{ $name }}</h3>
                    <p>{{ $text }}</p>
                </a>
            @endforeach
        </div>
    </section>

    @php(\dobron\BigPipe\BigPipe::page()->call('Home/StreamDemo', 'init', [\dobron\BigPipe\TransportMarker::element('demo')]))
    @php(\dobron\BigPipe\BigPipe::page()->call('tutorial/Code', 'highlight'))
@endsection
