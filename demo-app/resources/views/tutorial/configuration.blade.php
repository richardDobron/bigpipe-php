@extends('layout')

@section('header', 'Custom configuration')

@section('description', 'define() sends data to the browser as a module of its own. PHP decides the settings, your modules read them by name.')

@section('code', <<<'CODE'
// with the page
BigPipe::page()->define('AppConfig', [
    'locale' => 'en',
    'request' => ['timeout' => 8000, 'retries' => 2],
]);

// or with any response, replacing the module
return (new AsyncResponse())
    ->define('AppConfig', $newConfig)
    ->call('tutorial/ConfigReader', 'show', [
        TransportMarker::element('config-output'),
    ])
    ->send();

// JavaScript
const { locale } = requireModule('AppConfig');
CODE
)

@section('examples')
    <x-example title="A configuration that any response can change" caption="The configuration is a module of the page. Each response defines it again, and the module reads the new values when it is called.">
        <div class="surface">
            <div class="segmented" id="locale-switch">
                <a href="{{ url()->current() }}" class="on" ajaxify="{{ route('configuration.change') }}?locale=en" rel="async-post"
                   onclick="this.parentNode.querySelectorAll('a').forEach(a => a.classList.toggle('on', a === this))">English</a>
                <a href="{{ url()->current() }}" ajaxify="{{ route('configuration.change') }}?locale=sk" rel="async-post"
                   onclick="this.parentNode.querySelectorAll('a').forEach(a => a.classList.toggle('on', a === this))">Slovak</a>
            </div>
            <p id="config-output" class="output">Choose a language.</p>
        </div>
    </x-example>
@endsection
