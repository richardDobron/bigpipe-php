@foreach ($posts as $post)
    <li id="post-{{ $post->id }}" class="bg-white rounded-lg border border-gray-200 p-4 flex items-start justify-between gap-4">
        <div>
            <a href="{{ route('posts.show', $post) }}" class="font-semibold text-blue-700 hover:underline">{{ $post->title }}</a>
            <p class="text-sm text-gray-600 mt-1">{{ Str::limit($post->body, 140) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $post->author->name }} · {{ $post->created_at->diffForHumans() }}</p>
        </div>
        <a href="{{ route('posts.edit', $post) }}" ajaxify="{{ route('posts.delete-dialog', $post) }}" rel="dialog"
           class="text-sm text-red-600 hover:underline shrink-0">Delete</a>
    </li>
@endforeach
