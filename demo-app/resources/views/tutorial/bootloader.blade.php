@extends('layout')

@section('header', 'Bootloader')

@section('description')
    A module the page rarely needs, like an editor, does not have to be in its bundle. Name its files in the resource map
    and call it like any other module: the first response that calls it loads its CSS and JavaScript, then calls it.
@endsection

@section('bullet-description')
    <ul role="list">
        <li><div><h3>Bootloader::setResourceMap()</h3><p>Names of the static files, e.g. from the manifest of the bundler.</p></div></li>
        <li><div><h3>Bootloader::enableBootload()</h3><p>The files a module needs; with a priority, they are preloaded while the network is idle.</p></div></li>
        <li><div><h3>registerModules()</h3><p>The loaded script makes the module available when it runs.</p></div></li>
        <li><div><h3>Pagelet::addCss(), addJs()</h3><p>Pagelets use the names of the map too.</p></div></li>
    </ul>
@endsection

@section('code', <<<'CODE'
Bootloader::setResourceMap([
    'editor.css' => ['type' => 'css', 'src' => '/bootloaded/editor.css'],
    'editor.js' => ['type' => 'js', 'src' => '/bootloaded/editor.js'],
]);
Bootloader::enableBootload(['Editor' => ['editor.css', 'editor.js']]);

return (new AsyncResponse())
    ->call('Editor', 'open', [TransportMarker::element('draft')])
    ->send();

// /bootloaded/editor.js, not in the bundle
registerModules({ Editor: class { open(textarea) { … } } });
CODE
)

@section('examples')
    <x-example title="An editor loaded on demand" caption="Open the editor: the response names editor.css and editor.js, and the browser loads them before it calls Editor. Open it again, or after a page transition: they are loaded already, nothing is requested.">
        <div class="surface">
            <div class="field">
                <label for="draft">Draft</label>
                <textarea id="draft" rows="5" placeholder="Write something, then open the editor…">BigPipe sends the page in parts.</textarea>
            </div>
            <a href="{{ url()->current() }}" ajaxify="{{ route('bootloader.open') }}" rel="async-post" class="btn sm" style="margin-top:12px">Open the editor</a>
            <p class="hint">Not in the bundle: <code>public/bootloaded/editor.js</code></p>
        </div>
    </x-example>
@endsection
