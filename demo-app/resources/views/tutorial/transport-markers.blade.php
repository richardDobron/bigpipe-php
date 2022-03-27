@extends('layout')

@section('header', 'Transport Markers')

@section('description', 'TRANSPORT API')

@section('code', <<<'CODE'
namespace App\Http\Controllers;

use App\Models\User;
use dobron\BigPipe\TransportMarker;
use dobron\BigPipe\AsyncResponse;

class UsersController extends Controller
{
    private $response;

    public function __construct(AsyncResponse $response)
    {
        $this->response = $response;
    }

    public function insights()
    {
        ...

        $this->response->bigPipe()->require("require('Chart').setup()", [
            'element' => TransportMarker::transportElement('chart-div'),
            'dataPoints' => $this->response->transport()->transportSet([
                ['x' => 10, 'y' => 71],
                ['x' => 20, 'y' => 55],
                ['x' => 30, 'y' => 50],
                ['x' => 40, 'y' => 65],
            ]),
        ]);

        return response()->json($this->response->getResponse());
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
    <section class="mx-auto px-6 py-12 lg:pt-32 pb-0 even">
        <div class="text-left md:text-center">
            <h2 class="m-auto max-w-4xl text-4xl font-extrabold tracking-tight lg:text-6xl">Example</h2>
        </div>
        <div class="text-center">
            <div class="my-12 mx-auto max-w-6xl">
                <ul role="list" class="grid grid-cols-1">
                    <li class="relative flex flex-col">
                        <div class="group flex justify-center grow items-end">
                            <div class="relative max-h-64 w-80">
                                <div class="relative z-10 rounded-md bg-white p-4 text-left text-sm shadow-lg ring-1 ring-black ring-opacity-5">
                                    <div class="text-center rounded-md border-4 border-dashed border-slate-200 p-6 text-xs font-bold text-slate-300">
                                        <div id="data-box">
                                            <div class="flex flex-col items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                                                </svg>
                                                <a href="#" ajaxify="{{ route('transport-markers.collection') }}" rel="async-post">Click here to<br>load data</a>
                                            </div>
                                            <div class="hidden">
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="flex items-center justify-center text-xs font-normal text-gray-500 mt-3">
                                            <svg class="mr-2 w-3 h-3" aria-hidden="true" focusable="false" data-prefix="far" data-icon="question-circle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119.043 8 8 119.083 8 256c0 136.997 111.043 248 248 248s248-111.003 248-248C504 119.083 392.957 8 256 8zm0 448c-110.532 0-200-89.431-200-200 0-110.495 89.472-200 200-200 110.491 0 200 89.471 200 200 0 110.53-89.431 200-200 200zm107.244-255.2c0 67.052-72.421 68.084-72.421 92.863V300c0 6.627-5.373 12-12 12h-45.647c-6.627 0-12-5.373-12-12v-8.659c0-35.745 27.1-50.034 47.579-61.516 17.561-9.845 28.324-16.541 28.324-29.579 0-17.246-21.999-28.693-39.784-28.693-23.189 0-33.894 10.977-48.942 29.969-4.057 5.12-11.46 6.071-16.666 2.124l-27.824-21.098c-5.107-3.872-6.251-11.066-2.644-16.363C184.846 131.491 214.94 112 261.794 112c49.071 0 101.45 38.304 101.45 88.8zM298 368c0 23.159-18.841 42-42 42s-42-18.841-42-42 18.841-42 42-42 42 18.841 42 42z"></path></svg>
                                            Open developer tools console.
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 text-center">
                            <h3 class="pointer-events-none mt-2 block truncate text-2xl font-bold">Working with Transport Markers</h3>
                            <p class="pointer-events-none mt-4 block text-xl text-color-800">Through transport markers you can send HTML content but also transform the content into JavaScript objects (such as Map, Set or Element).</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </section>
@endsection
