@extends('layout')

@section('header', 'Refresh & Redirecting')

@section('description', 'You can set a delay (in milliseconds) to refresh current page or redirect to another.')

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
@endsection
