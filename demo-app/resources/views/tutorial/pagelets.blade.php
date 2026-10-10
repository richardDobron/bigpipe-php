@extends('layout')

@section('header', 'Streaming pagelets')

@section('description')
    This page was sent before its parts were ready. Every pagelet waits for a slow “API”, is rendered at the same time as the others,
    and is flushed into its placeholder as soon as it is done. One of them fails and shows its fallback; one waits for a later phase.
@endsection

@section('bullet-description')
    <ul role="list">
        <li><div><h3>defer() and content()</h3><p>The content is rendered when the pagelet is, not when the page is built.</p></div></li>
        <li><div><h3>BigPipe::stream()</h3><p>Flushes the page, then every pagelet as soon as it is rendered.</p></div></li>
        <li><div><h3>BigPipe::setParallel(true)</h3><p>Pagelets that wait with Pagelet::sleep() or await() do not hold up each other.</p></div></li>
        <li><div><h3>setFallback()</h3><p>A pagelet that throws is replaced by its fallback, the page goes on.</p></div></li>
        <li><div><h3>setPhase()</h3><p>A pagelet of a later phase is shown after the earlier ones.</p></div></li>
        <li><div><h3>refreshPagelet()</h3><p>A response renders a pagelet again and replaces it.</p></div></li>
    </ul>
@endsection

@section('code', <<<'CODE'
class FeedPagelet extends Pagelet
{
    protected mixed $fallback = '<p>The feed is not available.</p>';

    protected function content(): string
    {
        // Waits without blocking the other pagelets.
        $posts = Pagelet::await(fn () => $this->api->poll());

        return view('feed', ['posts' => $posts])->render();
    }
}

// The controller
BigPipe::setParallel(true);

return response()->stream(function () {
    echo view('page', ['feed' => new FeedPagelet()])->render();
    BigPipe::stream();             // every pagelet when it is ready
    echo '</body></html>';
}, 200, ['X-Accel-Buffering' => 'no']);
CODE
)

@php(\dobron\BigPipe\BigPipe::page()->call('tutorial/Arrival', 'done'))

@section('examples')
    <x-example class="wide" title="Five pagelets, one response" caption="The layout was flushed first, then every pagelet as soon as it was rendered; the bars end where each one was. Suggestions is in phase 1: rendered early, it is sent after the others. Shown at is when the browser displayed it (with the Vite dev server, once the page is parsed). Switch to one after another and compare.">
        <div class="pl-toolbar">
            <div class="segmented">
                <a href="?parallel=1" class="{{ $parallel ? 'on' : '' }}">Parallel</a>
                <a href="?parallel=0" class="{{ $parallel ? '' : 'on' }}">One after another</a>
            </div>
            <dl class="pl-summary">
                <div><dt>layout flushed at</dt><dd>{{ \App\Pagelets\Tutorial\SlowPagelet::elapsed() }} ms</dd></div>
                <div><dt>last pagelet rendered at</dt><dd id="last-flushed">…</dd></div>
            </dl>
        </div>
        <div class="pl-grid">
            @foreach (['profile', 'feed', 'stats', 'ads', 'suggestions'] as $id)
                <div class="pl-slot">{!! new \App\Pagelets\Tutorial\SlowPagelet($id) !!}</div>
            @endforeach
        </div>
    </x-example>

    <x-example title="Refresh a pagelet" caption="An AsyncResponse with refreshPagelet() renders the pagelet again on the server and replaces the one on the page.">
        <div class="row" style="justify-content:center">
            @foreach (['profile' => 'Profile', 'feed' => 'Feed', 'stats' => 'Statistics'] as $id => $label)
                <a href="{{ url()->current() }}" ajaxify="{{ route('pagelets.refresh', $id) }}" rel="async-post" class="btn ghost sm">Refresh {{ $label }}</a>
            @endforeach
        </div>
    </x-example>
@endsection
