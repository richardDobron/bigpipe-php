@extends('layouts.app')

@section('content')
    <a href="{{ route('posts.show', $post) }}" class="text-sm text-blue-700 hover:underline">← {{ $post->title }}</a>
    <h1 class="text-2xl font-bold mt-3 mb-1">Edit the post</h1>
    <p class="text-gray-600 mb-4">Change something and follow a link, or close the tab: you are asked first (<code>FormMonitor</code>). Saving makes the form clean.</p>

    <form id="post-form" action="{{ route('posts.update', $post) }}" method="POST" rel="async" class="space-y-3">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium" for="title">Title</label>
            <input id="title" name="title" value="{{ $post->title }}" class="w-full rounded border-gray-300">
            <p id="error-title" class="error"></p>
        </div>

        <div>
            <label class="block text-sm font-medium" for="body">Text</label>
            <textarea id="body" name="body" rows="8" class="w-full rounded border-gray-300">{{ $post->body }}</textarea>
            <p id="error-body" class="error"></p>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-3 py-2 rounded bg-blue-600 text-white text-sm">Save</button>
            <span class="form-loader text-sm text-gray-500">Saving...</span>
            <span id="edit-status" class="text-sm text-green-700"></span>
        </div>
    </form>

    @php
        \dobron\BigPipe\BigPipe::page()->call('bigpipe-util/dist/core/FormMonitor', null, [
            \dobron\BigPipe\TransportMarker::element('post-form'),
            ['message' => 'Leave without saving the post?'],
        ]);
    @endphp
@endsection
