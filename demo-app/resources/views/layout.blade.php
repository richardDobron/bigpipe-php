@extends('layouts.base')

@section('canvas')
    <section>
        <div class="page" style="padding-bottom:64px">
            <div class="eyebrow">
                <a href="/#tutorials" class="label">← Tutorials</a><span class="bar"></span>
            </div>

            <div style="display:grid;gap:48px;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));align-items:start">
                <div>
                    <h1 class="hero-title" style="font-size:clamp(32px,4.5vw,56px)">@yield('header')</h1>
                    <div class="lead tut-intro" style="margin-top:20px">@yield('description')</div>
                    <div class="tut-intro" style="margin-top:8px">@yield('bullet-description')</div>
                </div>
                <div class="code-window">
                    <div class="chrome"><i></i><i></i><i></i><span>Controller.php</span></div>
                    <pre class="line-numbers" style="margin:0;padding:6px 22px 22px 3.6em;overflow:auto;font-size:13px;line-height:1.6"><code class="language-php">@yield('code')</code></pre>
                </div>
            </div>
        </div>
    </section>

    <section class="page playground" style="padding-top:0">
        <div class="eyebrow"><span class="label">Try it</span></div>
        <div class="playground-grid">
            <div class="examples">@yield('examples')</div>
            <aside class="inspector">
                <div class="inspector-head">
                    <span class="live"></span>
                    <span class="title">Server responses</span>
                </div>
                <p class="inspector-empty">Use an example: what the server sends back shows up here, as DOM operations, module calls and payload.</p>
                <ol id="inspector-log"></ol>
            </aside>
        </div>
    </section>

    @php(\dobron\BigPipe\BigPipe::page()->call('tutorial/Code', 'highlight'))
    @php(\dobron\BigPipe\BigPipe::page()->call('tutorial/Inspector', 'init'))
@endsection
