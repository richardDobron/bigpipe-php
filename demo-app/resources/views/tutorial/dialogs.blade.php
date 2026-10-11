@extends('layout')

@section('header', 'Dialogs')

@section('description')
    A dialog the server opens: a <code>DialogResponse</code> built in PHP, a Blade view or a React component, also several
    of them stacked on each other. Every dialog behaves the same by default, and each behavior can be changed per dialog.
    See <a href="/docs/dialogs#options-and-behaviors">Dialogs</a> in the docs.
@endsection

@section('bullet-description')
    <ul role="list">
        <li><div><h3>setTitle / setBody / setFooter</h3><p>The parts of a dialog; a button with data-dismiss="modal" closes it.</p></div></li>
        <li><div><h3>setDialog</h3><p>The whole content of a dialog, e.g. a Blade view.</p></div></li>
        <li><div><h3>setController</h3><p>A JavaScript class created with the dialog, for its events (show, shown, hide, hidden) or logic.</p></div></li>
        <li><div><h3>closeDialog / closeDialogs</h3><p>Close the most recent dialog, or all of them.</p></div></li>
        <li><div><h3>setBackdrop / setKeyboard</h3><p>'static' for a backdrop that doesn't close the dialog, false for Esc that doesn't.</p></div></li>
        <li><div><h3>setHideOnSuccess</h3><p>Close the dialog when a request sent from inside of it succeeds.</p></div></li>
        <li><div><h3>setAutoFocus / setTrapFocus / setRefocus</h3><p>Focus the dialog when shown, keep Tab inside, give the focus back when closed. All on by default.</p></div></li>
        <li><div><h3>setAnimate / setPosition / setTimeout</h3><p>Fade in and out, the top margin or centered, show after a delay.</p></div></li>
    </ul>
@endsection

@section('code', <<<'CODE'
namespace App\Http\Controllers;

use App\Arch\BigPipe\DialogResponse;

class DialogController extends Controller
{
    public function showModal()
    {
        return (new DialogResponse())
            ->setTitle('Dialog title')
            ->setBody('HTML body')
            ->setFooter('HTML footer')
            ->dialog()
            ->send();

        // or the whole content of the dialog

        return (new DialogResponse())
            ->setDialog('HTML content')
            ->dialog()
            ->send();
    }

    public function showStaticModal()
    {
        // each behavior can be changed per dialog
        return (new DialogResponse())
            ->setTitle('Static backdrop')
            ->setBody('HTML body')
            ->setBackdrop('static')  // the backdrop doesn't close it
            ->setKeyboard(false)     // neither does Esc
            ->setHideOnSuccess()     // a request from inside does
            ->setPosition(centered: true)
            ->dialog()
            ->send();
    }
}
CODE
)

@section('examples')
    <x-example title="Dialogs from the server" caption="Each button asks the server for a dialog: a DialogResponse built in PHP, a Blade view, a React component, or several dialogs stacked on each other. When a dialog closes, the focus goes back to the link that opened it (its causal element), so a keyboard user continues where they were.">
        <div class="surface stack">
            @foreach ([
                ['Dialog from a model', 'DialogResponse', 'dialog.model-dialog'],
                ['Dialog from HTML', 'Blade view', 'dialog.html-dialog'],
                ['Dialog in React', 'React', 'dialog.react-dialog'],
                ['Multiple dialogs', 'stacked', 'dialog.common-dialog'],
            ] as [$label, $kind, $route])
                <a class="btn ghost block" href="{{ url()->current() }}" ajaxify="{{ route($route) }}" rel="async-post">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2.25" y="3.25" width="11.5" height="9.5" rx="2"/><path d="M2.5 6h11"/></svg>
                    {{ $label }}
                    <span class="kbd">{{ $kind }}</span>
                </a>
            @endforeach
        </div>
    </x-example>

    <x-example title="Behaviors" caption="Each button opens a dialog with one of its behaviors changed. The others stay the defaults: the focus moves into the dialog, Tab stays inside of it, Esc and a click on the backdrop close it, and the focus goes back to the button.">
        <div class="surface stack">
            @foreach ([
                ['Static backdrop', 'setBackdrop', 'static'],
                ['Hide on success', 'setHideOnSuccess', 'hide-on-success'],
                ['Animated and centered', 'setPosition', 'centered'],
                ['Delayed', 'setTimeout', 'delayed'],
                ['Focus stays on the page', 'setTrapFocus', 'free-focus'],
            ] as [$label, $setters, $behavior])
                <a class="btn ghost block" href="{{ url()->current() }}" ajaxify="{{ route('dialog.behavior', $behavior) }}" rel="async-post">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2.25" y="3.25" width="11.5" height="9.5" rx="2"/><path d="M2.5 6h11"/></svg>
                    {{ $label }}
                    <span class="kbd">{{ $setters }}</span>
                </a>
            @endforeach
        </div>
    </x-example>
@endsection
