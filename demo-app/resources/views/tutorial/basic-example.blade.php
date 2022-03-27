@extends('layout')

@section('header', 'Basic Usages')

@section('description', 'DOMOPS API')

@section('code', <<<'CODE'
namespace App\Http\Controllers;

use App\Models\User;
use dobron\BigPipe\AsyncResponse;

class UsersController extends Controller
{
    private $response;

    public function __construct(AsyncResponse $response)
    {
        $this->response = $response;
    }

    public function loadMore(Request $request)
    {
        ...

        $this->response->appendContent('#users > tbody', view('app.users.partials.table-body', compact('users')));

        return response()->json($this->response->getResponse());
    }
}
CODE
)

@section('bullet-description')
    <ul role="list" class="text-base text-gray-300">
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">setContent</h3>
                <p class="pointer-events-none mt-1 block text-color-800">Sets the content of an element.</p>
            </div>
        </li>
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">appendContent</h3>
                <p class="pointer-events-none mt-1 block text-color-800">Insert content as the last child of specified element.</p>
            </div>
        </li>
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">prependContent</h3>
                <p class="pointer-events-none mt-1 block text-color-800">Insert content as the first child of specified element.</p>
            </div>
        </li>
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">insertAfter</h3>
                <p class="pointer-events-none mt-1 block text-color-800">Insert content after specified element.</p>
            </div>
        </li>
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">insertBefore</h3>
                <p class="pointer-events-none mt-1 block text-color-800">Insert content before specified element.</p>
            </div>
        </li>
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">remove</h3>
                <p class="pointer-events-none mt-1 block text-color-800">Remove specified element and its children.</p>
            </div>
        </li>
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">replace</h3>
                <p class="pointer-events-none mt-1 block text-color-800">Replace specified element with content.</p>
            </div>
        </li>
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">eval</h3>
                <p class="pointer-events-none mt-1 block text-color-800">Evaluates JavaScript code represented as a string.</p>
            </div>
        </li>
    </ul>
@endsection

@php
        $bigPipe = new \dobron\BigPipe\BigPipe();
        $bigPipe->require("require('tutorial/IntervalUsage').init()", [
            route('basic-example.stats')
        ]);
@endphp

@section('examples')
    <section class="mx-auto px-6 py-12 lg:pt-32 pb-0 even">
        <div class="text-left md:text-center">
            <h2 class="m-auto max-w-4xl text-4xl font-extrabold tracking-tight lg:text-6xl">Examples</h2>
        </div>
        <div class="text-center">
            <div class="my-12 mx-auto max-w-6xl">
                <ul role="list" class="grid grid-cols-1 gap-x-4 gap-y-8 sm:gap-x-6 md:grid-cols-3 xl:gap-x-8">
                    <li class="relative flex flex-col">
                        <div class="group flex grow items-end">
                            <div class="grow" id="box-stats">
                                @include('partials.stats')
                            </div>
                        </div>
                        <div class="mt-8 text-left">
                            <h3 class="pointer-events-none mt-2 block truncate text-2xl font-bold">Request with Interval</h3>
                            <p class="pointer-events-none mt-4 block text-xl text-color-800">If it is not possible to use the ajaxify attribute, you can use AsyncRequest & AsyncResponse methods to do the same thing.</p>
                        </div>
                    </li>
                    <li class="relative flex flex-col">
                        <div class="group flex grow items-end">
                            <div class="grow">
                                <div class="relative max-h-64">
                                    <div class="relative z-10 rounded-md bg-white p-4 text-left text-sm shadow-lg ring-1 ring-black ring-opacity-5">
                                        <div class="divide-y divide-slate-200">
                                            <div class="grid grid-cols-2 gap-2 py-4">
                                                <div class="flex space-x-3 rounded-md">
                                                    <div>
                                                        <div class="font-bold">John Doe</div>
                                                        <div class="text-slate-400"><span class="">Home</span></div>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <a class="font-bold text-blue-500" href="#" ajaxify="{{ route('basic-example.show.phone', ['id' => 1]) }}" rel="async-post">+*******84</a></div>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2 py-4">
                                                <div class="flex space-x-3 rounded-md">
                                                    <div>
                                                        <div class="font-bold">Jane Doe</div>
                                                        <div class="text-slate-400"><span class="">Work</span></div>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <a class="font-bold text-blue-500" href="#" ajaxify="{{ route('basic-example.show.phone', ['id' => 2]) }}" rel="async-post">+*******11</a></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 text-left">
                            <h3 class="pointer-events-none mt-2 block truncate text-2xl font-bold">Click Interaction</h3>
                            <p class="pointer-events-none mt-4 block text-xl text-color-800">In this example, we use the ajaxify and rel attributes. They respond to the click event which calls AsyncRequest and displays the phone number.</p>
                        </div>
                    </li>
                    <li class="relative flex flex-col">
                        <div class="group flex grow items-end">
                            <div class="grow">
                                <div class="relative max-h-64">
                                    <div class="relative z-10 rounded-md bg-white p-4 text-left text-sm shadow-lg ring-1 ring-black ring-opacity-5">
                                        <div class="text-center rounded-md border-4 border-dashed border-slate-200 p-6 text-xs font-bold text-slate-300">
                                            <div id="image-box">
                                                <div class="flex flex-col items-center ">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" class="h-6 w-6 shrink-0" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    <a href="#" ajaxify="{{ route('basic-example.image') }}" rel="async-post">Click here to<br>load image</a>
                                                </div>
                                                <div class="hidden">
                                                    <img class="m-auto py-0.5">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 text-left">
                            <h3 class="pointer-events-none mt-2 block truncate text-2xl font-bold">Call JS Module from PHP</h3>
                            <p class="pointer-events-none mt-4 block text-xl text-color-800">It is not always appropriate to use DOMOPS. You can call a specific class method or a regular function with the custom arguments.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </section>
@endsection
