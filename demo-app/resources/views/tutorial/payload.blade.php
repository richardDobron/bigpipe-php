@extends('layout')

@section('header', 'Payload')

@section('description', 'The payload can contain any data you want to send to the frontend for possible further processing in JavaScript.')

@section('code', <<<'CODE'
namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use dobron\BigPipe\TransportMarker;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function checkUsername(Request $request)
    {
        ...

        return (new AsyncResponse())
            ->setPayload([
                'username' => $username,
                'status' => $status,
                'message' => TransportMarker::html($message),
            ])
            ->send();
    }
}
CODE
)

@php
        \dobron\BigPipe\BigPipe::page()->call('tutorial/UsernameChecker', 'init', [
            route('payload.check.username'),
        ]);
@endphp

@section('examples')
    <x-example title="A live username check" caption="Every keystroke (debounced) sends an AsyncRequest. The server answers with a payload, and the module of the page shows it.">
        <form class="surface" onsubmit="return false">
            <div class="field">
                <label for="username">Username</label>
                <div class="input-wrap">
                    <input type="text" autocomplete="off" name="username" id="username" placeholder="e.g. marvin42" spellcheck="false">
                    <span class="state" aria-hidden="true">
                        <svg class="ok" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m3.5 8.5 3 3 6-7"/></svg>
                        <svg class="no" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="m4.5 4.5 7 7m0-7-7 7"/></svg>
                    </span>
                </div>
                <p class="status-message" aria-live="polite"></p>
            </div>
            <p class="hint">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="6.25"/><path d="M8 7.25v4M8 4.75v.01" stroke-linecap="round"/></svg>
                Only usernames with a number are available.
            </p>
        </form>
    </x-example>
@endsection
