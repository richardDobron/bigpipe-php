@extends('layout')

@section('header', 'Payload')

@section('description', 'The payload can contain any data you want to send to the frontend for possible further processing in JavaScript.')

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

    public function checkUsername(Request $request)
    {
        ...

        $this->response->setPayload([
            'username' => $username,
            'status' => $status,
            'message' => ' ... ',
        ]);

        return $this->response->send();
    }
}
CODE
)

@php
        $bigPipe = new dobron\BigPipe\BigPipe();
        $bigPipe->require(
            "require('tutorial/UsernameChecker').init()",
            [
                route('payload.check.username')
            ]
        );
@endphp

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
                                <form>
                                    <div class="relative z-10 rounded-md bg-white p-4 text-left text-sm shadow-lg ring-1 ring-black ring-opacity-5">
                                        <label for="email" class="block text-sm font-medium text-slate-700">Username</label>
                                        <div class="mt-1">
                                            <input type="text" autocomplete="off" name="username" id="username" class="peer px-3 py-2 bg-white border shadow-sm border-slate-300 placeholder-slate-400 disabled:bg-slate-50 disabled:text-slate-500 disabled:border-slate-200 focus:outline-none focus:border-sky-500 focus:ring-sky-500 block w-full rounded-md sm:text-sm focus:ring-1 invalid:border-pink-500 invalid:text-pink-600 focus:invalid:border-pink-500 focus:invalid:ring-pink-500 disabled:shadow-none" value="" placeholder="Enter your username">
                                            <p class="mt-2 text-sm status-message"></p>
                                        </div>
                                        <div>
                                            <span class="flex items-center justify-center text-xs font-normal text-gray-500 mt-1">
                                                <svg class="mr-2 w-3 h-3" aria-hidden="true" focusable="false" data-prefix="far" data-icon="question-circle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119.043 8 8 119.083 8 256c0 136.997 111.043 248 248 248s248-111.003 248-248C504 119.083 392.957 8 256 8zm0 448c-110.532 0-200-89.431-200-200 0-110.495 89.472-200 200-200 110.491 0 200 89.471 200 200 0 110.53-89.431 200-200 200zm107.244-255.2c0 67.052-72.421 68.084-72.421 92.863V300c0 6.627-5.373 12-12 12h-45.647c-6.627 0-12-5.373-12-12v-8.659c0-35.745 27.1-50.034 47.579-61.516 17.561-9.845 28.324-16.541 28.324-29.579 0-17.246-21.999-28.693-39.784-28.693-23.189 0-33.894 10.977-48.942 29.969-4.057 5.12-11.46 6.071-16.666 2.124l-27.824-21.098c-5.107-3.872-6.251-11.066-2.644-16.363C184.846 131.491 214.94 112 261.794 112c49.071 0 101.45 38.304 101.45 88.8zM298 368c0 23.159-18.841 42-42 42s-42-18.841-42-42 18.841-42 42-42 42 18.841 42 42z"></path></svg>
                                                Only usernames that contain a number are available.
                                            </span>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="mt-8 text-center">
                            <h3 class="pointer-events-none mt-2 block truncate text-2xl font-bold">Payload Data</h3>
                            <p class="pointer-events-none mt-4 block text-xl text-color-800">
                                In this example, we use payload to verify the availability of username.<br>
                                Using the AsyncRequest method, we found out the availability status which was verified by the backend.
                            </p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </section>
@endsection
