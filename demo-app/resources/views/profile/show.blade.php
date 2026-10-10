@extends('layouts.app')

@section('content')
    <x-page-head eyebrow="06 / Profile" title="Profile">Choose an image: it is uploaded in the background with a progress bar, and the picture changes when it is done.</x-page-head>

    <div class="flex flex-wrap items-start gap-6">
        <img id="avatar" src="{{ auth()->user()->avatarUrl() }}" width="96" height="96" alt="" class="rounded-full border border-gray-200 bg-white">

        <form action="{{ route('avatar.update') }}" method="POST" rel="async" enctype="multipart/form-data" class="space-y-2 min-w-0 flex-1" style="min-width:min(100%,260px)">
            @csrf
            <input type="file" name="avatar" accept="image/*">
            <progress value="0" max="100"></progress>
            <p id="error-avatar" class="error"></p>
            <button type="submit" class="px-3 py-2 rounded bg-blue-600 text-white text-sm">Upload</button>
        </form>
    </div>
@endsection
