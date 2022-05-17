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

class DialogController extends Controller
{
    public function __construct(DialogResponse $response)
    {
        $this->response = $response;
    }

    public function showModal()
    {
        $this->response
            ->setTitle('Dialog title')
            ->setBody('HTML body')
            ->setFooter('HTML footer')
            ->dialog();

        // or

        $this->response
            ->setDialog('HTML content')
            ->dialog([
                'backdrop' => 'static',
            ]);

        return $this->response->send();
    }
}
CODE
)

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
                                <div class="relative rounded-md bg-white p-4 text-left text-sm shadow-lg ring-1 ring-black ring-opacity-5 flex flex-col gap-3">
                                    <a class="relative items-center rounded-md border font-semibold ring-offset-2 focus:outline-none focus:ring-2 text-white bg-blue-600 hover:bg-blue-700 border-blue-500 px-4 py-2 text-base rounded-md inline-flex" href="#" ajaxify="{{ route('dialog.model-dialog') }}" rel="async-post">
                                        <span class="flex gap-3 items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                            Open Dialog (using Model)
                                        </span>
                                    </a>
                                    <a class="relative items-center rounded-md border font-semibold ring-offset-2 focus:outline-none focus:ring-2 text-white bg-blue-600 hover:bg-blue-700 border-blue-500 px-4 py-2 text-base rounded-md inline-flex" href="#" ajaxify="{{ route('dialog.html-dialog') }}" rel="async-post">
                                        <span class="flex gap-3 items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                            Open Dialog (using HTML)
                                        </span>
                                    </a>
                                    <a class="relative items-center rounded-md border font-semibold ring-offset-2 focus:outline-none focus:ring-2 text-white bg-blue-600 hover:bg-blue-700 border-blue-500 px-4 py-2 text-base rounded-md inline-flex" href="#" ajaxify="{{ route('dialog.common-dialog') }}" rel="async-post">
                                        <span class="flex gap-3 items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                            Multiple Dialogs
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 text-center">
                            <h3 class="pointer-events-none mt-2 block truncate text-2xl font-bold">Modal Vanilla Dependency</h3>
                            <p class="mt-4 block text-xl text-color-800">We use the <a class="font-bold" target="_blank" href="https://github.com/KaneCohen/modal-vanilla">Modal Vanilla</a> dependency to display dialogs which is functionally and visually compatible with Bootstrap framework.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </section>
@endsection
