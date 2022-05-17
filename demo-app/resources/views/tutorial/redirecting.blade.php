@extends('layout')

@section('header', 'Refresh & Redirecting')

@section('description', 'You can set a delay (in milliseconds) to refresh current page or redirect to another.')

@section('code', <<<'CODE'
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UsersController extends Controller
{
    private $response;

    public function __construct(AsyncResponse $response)
    {
        $this->response = $response;
    }

    public function onboarding(Request $request)
    {
        ...

        $this->response->redirect('/', 500);

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
                                <div class="relative z-10 rounded-md bg-white p-4 text-left text-sm shadow-lg ring-1 ring-black ring-opacity-5 flex flex-col gap-3">
                                    <a class="relative items-center rounded-md border font-semibold ring-offset-2 focus:outline-none focus:ring-2 text-white bg-blue-600 hover:bg-blue-700 border-blue-500 px-4 py-2 text-base rounded-md inline-flex" href="#" ajaxify="{{ route('redirecting.reload') }}" rel="async-post">
                                        <span class="flex gap-3 items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Reload Page
                                        </span>
                                    </a>
                                    <a class="relative items-center rounded-md border font-semibold ring-offset-2 focus:outline-none focus:ring-2 text-white bg-blue-600 hover:bg-blue-700 border-blue-500 px-4 py-2 text-base rounded-md inline-flex" href="#" ajaxify="{{ route('redirecting.reload-delay') }}" rel="async-post">
                                        <span class="flex gap-3 items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Reload Page (after 250ms)
                                        </span>
                                    </a>
                                    <a class="relative items-center rounded-md border font-semibold ring-offset-2 focus:outline-none focus:ring-2 text-white bg-blue-600 hover:bg-blue-700 border-blue-500 px-4 py-2 text-base rounded-md inline-flex" href="#" ajaxify="{{ route('redirecting.redirect') }}" rel="async-post">
                                        <span class="flex gap-3 items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                            Redirect to Home
                                        </span>
                                    </a>
                                    <a class="relative items-center rounded-md border font-semibold ring-offset-2 focus:outline-none focus:ring-2 text-white bg-blue-600 hover:bg-blue-700 border-blue-500 px-4 py-2 text-base rounded-md inline-flex" href="#" ajaxify="{{ route('redirecting.redirect-delay') }}" rel="async-post">
                                        <span class="flex gap-3 items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Redirect to Home (after 500ms)
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </section>
@endsection
