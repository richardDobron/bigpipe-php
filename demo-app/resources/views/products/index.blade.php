@extends('layouts.app')

@section('content')
    <x-page-head eyebrow="02 / Shop" title="Shop">Add products: the badge in the header changes, nothing else on the page.</x-page-head>

    <ul class="grid sm:grid-cols-2 gap-3">
        @foreach ($products as $product)
            <li class="bg-white rounded-lg border border-gray-200 p-4 flex items-center justify-between">
                <div>
                    <p class="font-semibold">{{ $product->name }}</p>
                    <p class="text-sm text-gray-500">${{ number_format($product->price, 2) }}</p>
                </div>
                <a href="{{ route('cart.show') }}" ajaxify="{{ route('cart.add', $product) }}" rel="async-post"
                   class="px-3 py-2 rounded bg-blue-600 text-white text-sm">Add to cart</a>
            </li>
        @endforeach
    </ul>
@endsection
