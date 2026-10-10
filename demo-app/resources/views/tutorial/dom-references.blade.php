@extends('layout')

@section('header', 'DOM References')

@section('description', 'The server can define an element of the page as a module: any module requires it by name instead of looking it up.')

@section('code', <<<'CODE'
return (new AsyncResponse())
    ->defineElement('tutorial/Target', 'box-a')
    ->call('tutorial/Highlight', 'pulse')
    ->send();

// JavaScript: the module is the element
export default class Highlight {
    pulse() {
        requireModule('tutorial/Target')
            .classList.add('ring-4');
    }
}
CODE
)

@section('examples')
    <x-example class="wide" title="Elements as modules" caption="The server defines which element the module gets, and the module requires it by name: tutorial/Target.">
        <div class="targets">
            @foreach (['box-a', 'box-b', 'box-c'] as $id)
                <div class="target">
                    <div id="{{ $id }}" class="el">#{{ $id }}</div>
                    <a href="{{ url()->current() }}" ajaxify="{{ route('dom-references.highlight', $id) }}" rel="async-post" class="btn ghost sm">Highlight</a>
                </div>
            @endforeach
        </div>
    </x-example>
@endsection
