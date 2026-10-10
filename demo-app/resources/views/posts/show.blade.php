@extends('layouts.app')

@section('content')
    <a href="{{ route('posts.index') }}" class="text-sm text-blue-700 hover:underline">← Blog</a>

    <article class="mt-3">
        <h1 class="text-2xl font-bold">{{ $post->title }}</h1>
        <p class="text-xs text-gray-400 mt-1">{{ $post->author->name }} · {{ $post->created_at->diffForHumans() }}</p>
        <div class="prose mt-4 whitespace-pre-line">{{ $post->body }}</div>
        <p class="mt-4 text-sm">
            <a href="{{ route('posts.edit', $post) }}" class="text-blue-700 hover:underline">Edit</a>
            ·
            <a href="{{ route('posts.edit', $post) }}" ajaxify="{{ route('posts.delete-dialog', $post) }}" rel="dialog"
               class="text-red-600 hover:underline">Delete</a>
        </p>
    </article>

    <h2 class="text-lg font-semibold mt-8 mb-2">Comments</h2>

    <form id="comment-form" action="{{ route('comments.store', $post) }}" method="POST" rel="async" class="mb-4">
        @csrf
        <textarea name="body" rows="3" class="w-full rounded border-gray-300" placeholder="Write a comment..."></textarea>
        <p id="error-body" class="error"></p>
        <div class="flex items-center gap-3">
            <button type="submit" class="px-3 py-2 rounded bg-blue-600 text-white text-sm">Comment</button>
            <span class="form-loader text-sm text-gray-500">Sending...</span>
        </div>
    </form>

    <ul id="comments" class="space-y-2">
        @each('posts._comment', $post->comments, 'comment')
    </ul>
@endsection
