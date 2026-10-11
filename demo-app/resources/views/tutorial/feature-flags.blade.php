@extends('layout')

@section('header', 'Feature flags')

@section('description')
    The JavaScript calls one module, <code>tutorial/Greeting</code>, and the server decides which implementation that is:
    a variant of a feature flag or an A/B test for the user, or none at all. See
    <a href="/docs/how_it_works#modules-the-server-chooses">Modules the server chooses</a> in the docs.
@endsection

@section('bullet-description')
    <ul role="list">
        <li><div><h3>defineCond($module, $target)</h3><p>The browser requires $target whenever $module is required or called.</p></div></li>
        <li><div><h3>defineCond($module, null)</h3><p>Turns the module off: its calls do nothing.</p></div></li>
        <li><div><h3>ifRequired()</h3><p>Treats a module that is turned off as missing and runs the fallback.</p></div></li>
        <li><div><h3>Bootloader</h3><p>When the variants are bootloadable, only the chosen one is downloaded.</p></div></li>
    </ul>
@endsection

@section('code', <<<'CODE'
class FeatureFlagController extends Controller
{
    public function greet(Request $request)
    {
        // 'tutorial/GreetingClassic', 'tutorial/GreetingNext' or null (off)
        $variant = $this->variantOf($request->user());

        return (new AsyncResponse())
            ->defineCond('tutorial/Greeting', $variant)
            ->call('tutorial/Greeting', 'greet', [
                TransportMarker::element('greeting'), 'Ada',
            ])
            ->send();
    }
}

// JavaScript: a fallback when the server turned it off
ifRequired(
    'tutorial/Greeting',
    Greeting => console.log(Greeting.variant),
    () => console.log('The greeting is off'),
);
CODE
)

@section('examples')
    <x-example title="One module name, the variant the server chose" caption="Pick a variant: the server keeps it in your session. Say hello: the response chooses the module for tutorial/Greeting and calls it, and the status shows which one ran. Off: the call does nothing and ifRequired() runs its fallback.">
        <div class="surface">
            <div class="row" style="justify-content:space-between;align-items:center">
                @include('tutorial._flag-switch')
                <a href="{{ url()->current() }}" ajaxify="{{ route('feature-flags.greet') }}" rel="async-post" class="btn sm">Say hello</a>
            </div>
            <div id="greeting" class="greeting" aria-live="polite">Nothing ran yet.</div>
            <p id="flag-status" class="output">tutorial/Greeting is not defined yet.</p>
        </div>
    </x-example>
@endsection
