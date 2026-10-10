@php($scale = 3200)
<div class="pl-card {{ $failed ? 'is-failed' : '' }}" data-flushed="{{ $renderedAt }}">
    <div class="pl-head">
        <strong>{{ $title }}</strong>
        <span class="pl-tag">{{ $phase ? 'phase '.$phase : '#pagelet_'.$id }}</span>
    </div>
    @if ($failed)
        <p class="pl-note">The API failed: this is the fallback of the pagelet. The rest of the page is fine.</p>
    @else
        <span class="pl-lines"><i></i><i></i><i></i></span>
    @endif
    <div class="pl-bar" title="rendered {{ $renderedAt }} ms after the request started"><i style="width:{{ min(100, $renderedAt / $scale * 100) }}%"></i></div>
    <dl class="pl-times">
        <div><dt>API</dt><dd>{{ (int) ($delay * 1000) }} ms</dd></div>
        <div><dt>rendered at</dt><dd>{{ $renderedAt }} ms</dd></div>
        <div><dt>shown at</dt><dd data-shown>…</dd></div>
    </dl>
</div>
