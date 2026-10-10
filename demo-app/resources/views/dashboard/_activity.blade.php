<ul class="space-y-2 text-sm">
    @forelse ($comments as $comment)
        <li>
            <span class="font-medium">{{ $comment->author->name }}</span> on
            <a href="{{ route('posts.show', $comment->post) }}" class="text-blue-700 hover:underline">{{ Str::limit($comment->post->title, 40) }}</a>
            <p class="text-gray-500">{{ Str::limit($comment->body, 80) }}</p>
        </li>
    @empty
        <li class="text-gray-500">No comments yet.</li>
    @endforelse
</ul>
