@extends('layout')

@section('header', 'Expired CSRF tokens')

@section('description', 'A token expires with the session. The browser gets a new one and sends the request again, so the user does not lose what they did.')

@section('code', <<<'CODE'
// bootstrap/app.php
$exceptions->render(function (HttpException $e, Request $request) {
    // Laravel turns a TokenMismatchException into a 419
    if ($e->getStatusCode() === 419 && $request->ajax()) {
        return (new AsyncResponse())
            ->retryWithCSRFToken(csrf_token())
            ->send();
    }
});

// where the token is set, e.g. in a middleware
BigPipe::setCSRFToken(
    csrf_token(),
    refreshUri: route('csrf-token', absolute: false)
);
CODE
)

@section('bullet-description')
    <ul role="list" class="text-base text-gray-300">
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">Answer the rejected request</h3>
                <p class="pointer-events-none mt-1 block text-color-800">The response carries the new token. The browser applies it and sends the request again, once.</p>
            </div>
        </li>
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">Or name a refresh URL</h3>
                <p class="pointer-events-none mt-1 block text-color-800">On a 419 the browser requests the URL, which answers with a new token, and then repeats the request.</p>
            </div>
        </li>
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">Laravel 13</h3>
                <p class="pointer-events-none mt-1 block text-color-800">A same-origin request of a modern browser passes on its origin alone. This demo checks the token too, to show the recovery.</p>
            </div>
        </li>
    </ul>
@endsection

@section('examples')
    <x-example title="An expired token" caption="Watch the responses: the save is rejected with a new token, sent again, and the user only sees the result.">
        <div class="surface">
            <ol class="steps-list">
                <li>
                    <div>
                        <p>Replace the token of the session, as if it had expired.</p>
                        <a href="{{ url()->current() }}" ajaxify="{{ route('csrf.expire') }}" rel="async-post" class="btn ghost sm">Expire the token</a>
                    </div>
                </li>
                <li>
                    <div>
                        <p>Save. The request still carries the old token.</p>
                        <a href="{{ url()->current() }}" ajaxify="{{ route('csrf.save') }}" rel="async-post" class="btn sm">Save</a>
                    </div>
                </li>
            </ol>
            <p id="csrf-status" class="status-line" aria-live="polite"></p>
        </div>
    </x-example>
@endsection
