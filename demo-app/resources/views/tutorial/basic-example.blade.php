@extends('layout')

@section('header', 'Basic Usages')

@section('description', 'DOMOPS API')

@section('code', <<<'CODE'
namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;

class PostController extends Controller
{
    public function loadMore(Request $request)
    {
        ...

        return (new AsyncResponse())
            ->appendContent('#posts', view('posts.items', compact('posts'))->render())
            ->send();
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
                <p class="pointer-events-none mt-1 block text-color-800">Evaluates JavaScript code represented as a string. Deprecated: call a JavaScript module instead.</p>
            </div>
        </li>
    </ul>
@endsection

@php
        \dobron\BigPipe\BigPipe::page()->call('tutorial/IntervalUsage', 'init', [
            route('basic-example.stats'),
        ]);
@endphp

@section('examples')
    <x-example title="A request on an interval" caption="Where the ajaxify attribute does not fit, AsyncRequest sends the request from JavaScript; the response replaces the numbers with setContent.">
        <div id="box-stats">
            @include('partials.stats')
        </div>
        <p class="polling">a request every 2.5 s</p>
    </x-example>

    <div class="examples cols-2">
        <x-example title="A click that replaces the link" caption="The ajaxify and rel attributes send the request; the response replaces the clicked link with the number.">
            <div class="surface contacts">
                @foreach ([[1, 'John Doe', 'Home', 'JD', '84'], [2, 'Jane Doe', 'Work', 'JD', '11']] as [$id, $name, $kind, $initials, $end])
                    <div class="contact">
                        <span class="avatar">{{ $initials }}</span>
                        <span class="who"><strong>{{ $name }}</strong><span>{{ $kind }}</span></span>
                        <a class="reveal" href="{{ url()->current() }}" ajaxify="{{ route('basic-example.show.phone', ['id' => $id]) }}" rel="async-post">+••• •• {{ $end }}</a>
                    </div>
                @endforeach
            </div>
        </x-example>

        <x-example title="A JavaScript module called from PHP" caption="Not everything is a DOM operation: the response calls the set() method of tutorial/Image with a URL.">
            <div class="dropzone">
                <div id="image-box">
                    <div>
                        <a href="{{ url()->current() }}" ajaxify="{{ route('basic-example.image') }}" rel="async-post">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 19.5h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Z"/></svg>
                            Load the image
                        </a>
                    </div>
                    <div class="hidden">
                        <img alt="">
                    </div>
                </div>
            </div>
        </x-example>
    </div>
@endsection
