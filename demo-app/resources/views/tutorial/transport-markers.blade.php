@extends('layout')

@section('header', 'Transport Markers')

@section('description', 'TRANSPORT API')

@section('code', <<<'CODE'
namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use dobron\BigPipe\TransportMarker;

class InsightsController extends Controller
{
    public function chart()
    {
        ...

        return (new AsyncResponse())
            ->call('Chart', 'setup', [
                'element' => TransportMarker::element('chart-div'),
                'dataPoints' => TransportMarker::set([
                    ['x' => 10, 'y' => 71],
                    ['x' => 20, 'y' => 55],
                    ['x' => 30, 'y' => 50],
                    ['x' => 40, 'y' => 65],
                ]),
            ])
            ->send();
    }
}
CODE
)

@section('bullet-description')
    <ul role="list" class="text-base text-gray-300">
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">transportHtml</h3>
                <p class="pointer-events-none mt-1 block text-color-800">You can send HTML (or text) content via the __html marker.</p>
            </div>
        </li>
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">transportElement</h3>
                <p class="pointer-events-none mt-1 block text-color-800">If you specify an element ID, the __e marker will represent the element whose id property matches the specified string.</p>
            </div>
        </li>
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">transportMap</h3>
                <p class="pointer-events-none mt-1 block text-color-800">The __map tag creates a Map object holds key-value pairs and remembers the original insertion order of the keys.</p>
            </div>
        </li>
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">transportSet</h3>
                <p class="pointer-events-none mt-1 block text-color-800">The __set tag creates a Set object that lets you store unique values.</p>
            </div>
        </li>
    </ul>
@endsection

@section('examples')
    <x-example title="Map, Set and Element from PHP" caption="Transport markers turn values of the response into JavaScript objects: the module gets an Element, a Map and a Set, and logs them to the console.">
        <div class="console" id="data-box">
            <div class="placeholder">
                <a href="{{ url()->current() }}" ajaxify="{{ route('transport-markers.collection') }}" rel="async-post">
                    <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m4.5 5 3 3-3 3M9 11h3"/></svg>
                    Load the data
                </a>
            </div>
            <div class="hidden"></div>
        </div>
        <p class="hint" style="justify-content:center">The same lines are in the console of the developer tools.</p>
    </x-example>
@endsection
