@extends('layout')

@section('header', 'Forms')

@section('description', 'This example illustrates an asynchronous form that is processed in the background instead of refreshing the entire page.')

@section('code', <<<'CODE'
namespace App\Http\Controllers;

class FormController extends Controller
{
    public function __construct(AsyncResponse $response)
    {
        $this->response = $response;
    }

    public function registration()
    {
        ...

        if ($error) {
            $this->response->bigPipe()->require("require('Toastr').error()", [
                $error,
            ]);

            return response()->json($this->response->getResponse());
        }

        ...

        return response()->json($this->response->getResponse());
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
                                <div class="relative z-10 rounded-md bg-white p-4 text-left text-sm shadow-lg ring-1 ring-black ring-opacity-5">
                                    <form method="POST" action="{{ route('forms.registration') }}" rel="async">
                                        <label for="full_name" class="block text-sm font-medium text-slate-700">Full name</label>
                                        <div class="mt-1">
                                            <input type="text" name="full_name" id="full_name" class="peer px-3 py-2 bg-white border shadow-sm border-slate-300 placeholder-slate-400 disabled:bg-slate-50 disabled:text-slate-500 disabled:border-slate-200 focus:outline-none focus:border-sky-500 focus:ring-sky-500 block w-full rounded-md sm:text-sm focus:ring-1 invalid:border-pink-500 invalid:text-pink-600 focus:invalid:border-pink-500 focus:invalid:ring-pink-500 disabled:shadow-none" value="" placeholder="Full name">
                                        </div>
                                        <label for="email" class="block text-sm font-medium text-slate-700 mt-2">E-mail</label>
                                        <div class="mt-1">
                                            <input type="email" name="email" id="email" class="peer px-3 py-2 bg-white border shadow-sm border-slate-300 placeholder-slate-400 disabled:bg-slate-50 disabled:text-slate-500 disabled:border-slate-200 focus:outline-none focus:border-sky-500 focus:ring-sky-500 block w-full rounded-md sm:text-sm focus:ring-1 invalid:border-pink-500 invalid:text-pink-600 focus:invalid:border-pink-500 focus:invalid:ring-pink-500 disabled:shadow-none" value="" placeholder="E-mail">
                                        </div>
                                        <div class="mt-2 flex">
                                            <button class="flex-1 relative justify-center items-center rounded-md border font-semibold ring-offset-2 focus:outline-none focus:ring-2 text-white bg-blue-600 hover:bg-blue-700 border-blue-500 px-4 py-2 text-base rounded-md inline-flex" href="#" ajaxify="{{ route('redirecting.redirect-delay') }}" rel="async-post">
                                                Submit
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 text-center">
                            <h3 class="pointer-events-none mt-2 block truncate text-2xl font-bold">Form Validation</h3>
                            <p class="pointer-events-none mt-4 block text-xl text-color-800">This form is sent asynchronously, if the backend detects invalid data in the form, it will display them via toast.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </section>
@endsection
