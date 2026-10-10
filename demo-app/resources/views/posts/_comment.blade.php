<li class="bg-white rounded border border-gray-200 p-3 text-sm">
    <span class="font-medium">{{ $comment->author->name }}</span>
    <span class="text-gray-400 text-xs">{{ $comment->created_at->diffForHumans() }}</span>
    <p class="mt-1">{{ $comment->body }}</p>
</li>
