@extends('layout')

@section('header', 'Refresh & Redirecting')

@section('description')
    A response can reload the page or send the browser to another URL, right away or after a delay (in milliseconds).
    A redirect can also replace the page in the history, or keep the layout with a page transition.
    See <a href="/docs/redirecting">Redirecting</a> in the docs.
@endsection

@section('bullet-description')
    <ul role="list">
        <li><div><h3>reload($delay)</h3><p>Reloads the page, now or after the delay.</p></div></li>
        <li><div><h3>redirect($url, $delay)</h3><p>Loads the URL in full, now or after the delay.</p></div></li>
        <li><div><h3>replace: true</h3><p>Replaces this page in the history: the back button does not return to it, e.g. to a form that was sent.</p></div></li>
        <li><div><h3>transition: true</h3><p>Loads the URL with a page transition: the layout stays and only the content is replaced. Keep it off when the layout changes, e.g. after a login.</p></div></li>
    </ul>
@endsection

@section('code', <<<'CODE'
namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function onboarding(Request $request)
    {
        ...

        return (new AsyncResponse())
            ->redirect('/', 500)
            ->send();
    }

    public function register(Request $request)
    {
        ...

        // The back button does not return to the sent form,
        // and the next page keeps the layout.
        return (new AsyncResponse())
            ->redirect('/', replace: true, transition: true)
            ->send();
    }
}
CODE
)

@section('examples')
    <x-example title="Reload and redirect" caption="The response tells the browser to reload the page or to go to another URL, right away or after a delay.">
        <div class="surface stack">
            @foreach ([
                ['Reload the page', 'now', 'redirecting.reload', 'M13.5 8a5.5 5.5 0 1 1-1.6-3.9M13.5 2.5v3h-3'],
                ['Reload the page', '250 ms', 'redirecting.reload-delay', 'M8 4.75V8l2 2M14 8A6 6 0 1 1 2 8a6 6 0 0 1 12 0Z'],
                ['Redirect to home', 'now', 'redirecting.redirect', 'M6.5 3.5h-3v9h9v-3M9 2.5h4.5V7M13.5 2.5 7 9'],
                ['Redirect to home', '500 ms', 'redirecting.redirect-delay', 'M8 4.75V8l2 2M14 8A6 6 0 1 1 2 8a6 6 0 0 1 12 0Z'],
            ] as [$label, $when, $route, $icon])
                <a class="btn ghost block" href="{{ url()->current() }}" ajaxify="{{ route($route) }}" rel="async-post">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                    {{ $label }}
                    <span class="kbd">{{ $when }}</span>
                </a>
            @endforeach
        </div>
    </x-example>

    <x-example title="Replace the history, keep the layout" caption="Replace: the tutorial is replaced in the history, so the back button on the home page skips it. Page transition: only the content is loaded, the top bar stays and nothing flashes, like when you follow a link.">
        <div class="surface stack">
            @foreach ([
                ['Redirect to home', 'replace', 'redirecting.redirect-replace', 'M2.5 8h11M10 4.5 13.5 8 10 11.5M2.5 3.5v9'],
                ['Redirect to home', 'page transition', 'redirecting.redirect-transition', 'M2.25 3.25h11.5v9.5H2.25zM2.5 6h11M6 9h4'],
            ] as [$label, $when, $route, $icon])
                <a class="btn ghost block" href="{{ url()->current() }}" ajaxify="{{ route($route) }}" rel="async-post">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                    {{ $label }}
                    <span class="kbd">{{ $when }}</span>
                </a>
            @endforeach
        </div>
    </x-example>
@endsection
