@extends('layouts.base')

@section('canvas')
    <div class="page">
        <p class="demo-note">This is a demo: the data is reset every hour, so what you add, change or delete here does not stay.</p>
        @yield('content')
    </div>
@endsection
