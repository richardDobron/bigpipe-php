@extends('layout')

@section('header', 'Lazy pagelets')

@section('description')
    A part of the page that is not needed right away is not rendered with it. The page gets a placeholder,
    and the browser asks for the pagelet when the placeholder becomes visible, or once the browser is idle.
@endsection

@section('bullet-description')
    <ul role="list">
        <li><div><h3>Pagelet::lazy($url)</h3><p>The placeholder of a pagelet class, loaded from the URL.</p></div></li>
        <li><div><h3>LazyPagelet::LOAD_VISIBLE</h3><p>When the placeholder scrolls into view (the default).</p></div></li>
        <li><div><h3>LazyPagelet::LOAD_IDLE</h3><p>Once the browser has nothing else to do.</p></div></li>
        <li><div><h3>$response->pagelet()</h3><p>The endpoint answers with the pagelet, which replaces its placeholder.</p></div></li>
    </ul>
@endsection

@section('code', <<<'CODE'
// The page gets only a placeholder: nothing is rendered yet
$comments = CommentsPagelet::lazy(
    route('comments.pagelet', $post),
    placeholder: '<p>Loading the comments…</p>',
);

return view('post', compact('post', 'comments'));

// The endpoint
public function comments(Post $post)
{
    return (new AsyncResponse())
        ->pagelet(new CommentsPagelet($post))
        ->send();
}
CODE
)

@php($skeleton = '<div class="pl-card is-waiting"><div class="pl-head"><strong>Not loaded yet</strong></div><span class="pl-lines"><i></i><i></i><i></i></span></div>')

@section('examples')
    <x-example title="Loaded once the browser is idle" caption="The page was ready without it. Right after the page loaded, the browser requested it in the background: see the first response.">
        {!! new \dobron\BigPipe\LazyPagelet('related', route('lazy-pagelets.load', 'related'), [], $skeleton, \dobron\BigPipe\LazyPagelet::LOAD_IDLE) !!}
    </x-example>

    <div class="scroll-hint">
        <span>Scroll down</span>
        <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3v10m-4-4 4 4 4-4"/></svg>
    </div>

    <x-example title="Loaded when it becomes visible" caption="Nothing was requested for this one until its placeholder scrolled into view. Infinite scroll works the same way: see the blog of the demo app.">
        {!! new \dobron\BigPipe\LazyPagelet('comments', route('lazy-pagelets.load', 'comments'), [], $skeleton) !!}
    </x-example>
@endsection
