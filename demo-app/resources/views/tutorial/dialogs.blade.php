@extends('layout')

@section('header', 'Dialogs')

@section('description')
    This example illustrates dynamic opening of a dialog but also working with multiple dialogs at once.

    <ul role="list" class="text-base text-gray-300">
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">setController</h3>
                <p class="pointer-events-none mt-1 block text-color-800">Sets the JavaScript class controller - if you need to register an extra event listeners (show, shown, hide, hidden) or logic.</p>
            </div>
        </li>
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">setTitle</h3>
                <p class="pointer-events-none mt-1 block text-color-800">Sets the title of a dialog.</p>
            </div>
        </li>
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">setBody</h3>
                <p class="pointer-events-none mt-1 block text-color-800">Sets the body of a dialog.</p>
            </div>
        </li>
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">setFooter</h3>
                <p class="pointer-events-none mt-1 block text-color-800">Sets the footer of a dialog.</p>
            </div>
        </li>
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">setDialog</h3>
                <p class="pointer-events-none mt-1 block text-color-800">Sets the whole content of a dialog.</p>
            </div>
        </li>
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">closeDialogs</h3>
                <p class="pointer-events-none mt-1 block text-color-800">Close all opened dialogs.</p>
            </div>
        </li>
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">closeDialog</h3>
                <p class="pointer-events-none mt-1 block text-color-800">Close only current dialog.</p>
            </div>
        </li>
        <li class="relative flex flex-col">
            <div class="mt-8 text-left">
                <h3 class="pointer-events-none mt-2 block truncate font-bold">dialog</h3>
                <p class="pointer-events-none mt-1 block text-color-800">Render defined dialog.</p>
            </div>
        </li>
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
            ->setBackdrop('static')
            ->dialog()
            ->send();

        // or the whole content of the dialog

        return (new DialogResponse())
            ->setDialog('HTML content')
            ->dialog()
            ->send();
    }
}
CODE
)

@section('examples')
    <x-example title="Dialogs from the server" caption="Each button asks the server for a dialog: a DialogResponse built in PHP, a Blade view, a React component, or several dialogs stacked on each other.">
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
@endsection
