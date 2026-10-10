@extends('layout')

@section('header', 'Forms')

@section('description')
    A form with <code>rel="async"</code> is sent in the background instead of loading a new page. A validation error comes back as an
    error response, and a <code>FormMonitor</code> asks before the user leaves the page with what they typed.
@endsection

@section('bullet-description')
    <ul role="list">
        <li><div><h3>rel="async"</h3><p>The form is sent with AsyncRequest; the response updates the page.</p></div></li>
        <li><div><h3>setError()</h3><p>The request failed: its error handler runs, the form keeps its changes.</p></div></li>
        <li><div><h3>FormMonitor</h3><p>Asks before a link, a page transition or a closed tab throws away what was typed.</p></div></li>
        <li><div><h3>success event</h3><p>A successful response makes the form clean again.</p></div></li>
    </ul>
@endsection

@section('code', <<<'CODE'
class FormController extends Controller
{
    public function registration(Request $request)
    {
        $response = new AsyncResponse();

        if ($error = $this->validate($request)) {
            // An error: the form keeps its unsaved changes.
            return $response->setError('Check the form', $error)->send();
        }

        return $response
            ->replace('#registration', view('form-success')->render())
            ->send();
    }
}

// The page: watch the form for unsaved changes
BigPipe::page()->call('bigpipe-util/dist/core/FormMonitor', null, [
    TransportMarker::element('registration'),
    ['message' => 'Leave without creating the account?'],
]);
CODE
)

@php(\dobron\BigPipe\BigPipe::page()->call('tutorial/FormState', 'watch', [
    \dobron\BigPipe\TransportMarker::element('registration'),
    \dobron\BigPipe\TransportMarker::element('form-state'),
    'Leave without creating the account?',
]))

@section('examples')
    <x-example title="Validation and unsaved changes" caption="Type something, then follow a link: you are asked first. Submit with a field empty: the error comes back as a toast and the changes stay unsaved. Fill in both: the server replaces the form, and leaving no longer asks.">
        <div class="surface">
            <form id="registration" method="POST" action="{{ route('forms.registration') }}" rel="async">
                <div class="field">
                    <label for="full_name">Full name</label>
                    <input type="text" name="full_name" id="full_name" placeholder="Ada Lovelace" autocomplete="off">
                </div>
                <div class="field">
                    <label for="email">E-mail</label>
                    <input type="email" name="email" id="email" placeholder="ada@example.com" autocomplete="off">
                </div>
                <button type="submit" class="btn" style="width:100%;margin-top:16px">Create account</button>
            </form>
            <p id="form-state" class="form-state" aria-live="polite"></p>
        </div>
    </x-example>
@endsection
