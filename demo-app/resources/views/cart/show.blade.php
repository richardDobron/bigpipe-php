@extends('layouts.app')

@section('content')
    <x-page-head eyebrow="03 / Cart" title="Cart">Removing a line updates the line, the total and the badge from one response.</x-page-head>

    <ul id="cart-lines" class="space-y-2">
        @foreach ($lines as $line)
            @include('cart._line', ['line' => $line])
        @endforeach
    </ul>

    <p id="cart-empty" class="text-gray-500 mt-2">{{ $lines->isEmpty() ? 'Your cart is empty.' : '' }}</p>

    <p class="mt-6 text-lg">Total: $<span id="cart-total" class="font-semibold">{{ number_format($lines->sum->total(), 2) }}</span></p>
@endsection
