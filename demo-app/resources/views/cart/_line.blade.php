<li id="line-{{ $line->id }}" class="bg-white rounded-lg border border-gray-200 p-3 flex items-center justify-between">
    <span>{{ $line->product->name }} × {{ $line->quantity }}</span>
    <span class="flex items-center gap-4">
        <span class="text-sm text-gray-500">${{ number_format($line->total(), 2) }}</span>
        <a href="{{ route('cart.show') }}" ajaxify="{{ route('cart.remove', $line) }}" rel="async-post"
           class="text-sm text-red-600 hover:underline">Remove</a>
    </span>
</li>
