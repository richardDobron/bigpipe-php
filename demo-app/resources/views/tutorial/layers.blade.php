@extends('layout')

@section('header', 'Layers')

@section('description')
    A menu, a tooltip or a popover is a layer: an element that floats above the page. The server creates it as an
    instance of <code>ContextualLayer</code>, with the behaviors it needs, next to the button that sent the request.
    See <a href="https://github.com/richardDobron/bigpipe-util/blob/main/docs/layer.md#from-the-server">Layer</a> in the
    docs of bigpipe-util, and <a href="/docs/transport_markers#relative">TransportMarker::relative()</a>.
@endsection

@section('bullet-description')
    <ul role="list">
        <li><div><h3>instance($module, $args)</h3><p>The browser creates new ContextualLayer(...$args) once; call() calls its methods.</p></div></li>
        <li><div><h3>TransportMarker::relative()</h3><p>The element that sent the request: the menu is placed next to the button that was clicked.</p></div></li>
        <li><div><h3>TransportMarker::module()</h3><p>The behaviors are classes: HideOnBlur, HideOnEscape, HideOnSuccess, DestroyOnHide.</p></div></li>
        <li><div><h3>ModuleRegistry</h3><p>The layers and behaviors of bigpipe-util are modules the server can require by name.</p></div></li>
    </ul>
@endsection

@section('code', <<<'CODE'
use dobron\BigPipe\TransportMarker;

$layer = 'bigpipe-util/dist/core/layer/';

$response = new AsyncResponse();

$response
    ->instance($layer . 'ContextualLayer', [
        view('menu')->render(),
        [
            // the button that sent the request
            'context' => TransportMarker::relative(),
            'position' => 'below',
            'hideOnSuccess' => true,
            'behaviors' => [
                TransportMarker::module($layer . 'HideOnBlur'),
                TransportMarker::module($layer . 'HideOnEscape'),
                TransportMarker::module($layer . 'HideOnSuccess'),
                TransportMarker::module($layer . 'DestroyOnHide'),
            ],
        ],
    ])
    ->call('show');

return $response->send();
CODE
)

@section('examples')
    <x-example title="A menu from the server" caption="Each button asks the server for a menu, and the menu opens next to the button that sent the request. Click outside of it or press Esc to hide it; pick an item: its request succeeds and the menu hides itself. A hidden menu is destroyed.">
        <div class="surface row" style="justify-content:center;gap:12px">
            <a href="{{ url()->current() }}" ajaxify="{{ route('layers.menu', 'below') }}" rel="async-post" class="btn ghost sm" aria-haspopup="menu">
                Actions
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m4.5 6.5 3.5 3.5 3.5-3.5"/></svg>
            </a>
            <a href="{{ url()->current() }}" ajaxify="{{ route('layers.menu', 'right') }}" rel="async-post" class="btn ghost sm" aria-haspopup="menu">
                More
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6.5 4.5 3.5 3.5-3.5 3.5"/></svg>
            </a>
        </div>
    </x-example>
@endsection
