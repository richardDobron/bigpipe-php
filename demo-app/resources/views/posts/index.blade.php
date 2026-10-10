@extends('layouts.app')

@section('content')
    <x-page-head eyebrow="01 / Blog" title="Blog">Scroll down: the next posts load when the link comes near (<code>MorePager</code>). Open a post: the link is a page transition.</x-page-head>

    <ul id="posts" class="space-y-3">@include('posts._items', ['posts' => $posts])</ul>

    @if ($posts->hasMorePages())
        <div class="mt-6 text-center">{!! new \dobron\BigPipe\MorePager($posts->nextPageUrl(), 'Load more posts') !!}</div>
    @endif
@endsection
