@extends('layouts.base')

@section('canvas')
    <div class="page">
        <p class="demo-note">This is your own playground: nobody else sees what you add, change or delete here, and it is removed an hour after you arrive.</p>
        @yield('content')
    </div>
@endsection
