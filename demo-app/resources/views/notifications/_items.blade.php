@forelse ($items as $item)
    <li class="bg-white rounded border border-gray-200 p-3 text-sm">
        {{ $item->title }} <span class="text-gray-400 text-xs">{{ $item->created_at->diffForHumans() }}</span>
    </li>
@empty
    <li class="text-gray-500 text-sm">Nothing new.</li>
@endforelse
