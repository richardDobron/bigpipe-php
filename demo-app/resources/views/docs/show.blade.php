@extends('layouts.base')

@section('canvas')
    <div class="docs">
        <input type="checkbox" id="docs-menu" class="docs-menu-toggle" aria-hidden="true" tabindex="-1">
        <label for="docs-menu" class="docs-menu-button"><span>Menu</span><b>{{ $heading }}</b></label>
        <nav class="docs-nav" aria-label="Documentation">
            <div class="docs-versions" aria-label="Version">
                @foreach ($versions as $item)
                    <a href="{{ $item['url'] }}" class="{{ $item['version'] === $version ? 'active' : '' }}"
                       @if ($item['version'] === $version) aria-current="true" @endif>{{ $item['version'] }}{{ $item['version'] === $latest ? ' (latest)' : '' }}</a>
                @endforeach
            </div>
            @foreach ($sidebar as $name => $items)
                <div class="docs-group">
                    <p class="label">{{ $name }}</p>
                    @foreach ($items as $item)
                        <a href="{{ $item['url'] }}" class="{{ $item['slug'] === $slug ? 'active' : '' }}">{{ $item['label'] }}</a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        <article class="docs-body">
            @if ($version !== $latest)
                <p class="docs-outdated">
                    This is the documentation of BigPipe {{ $version }}. See <a href="{{ $latestUrl }}">this page for {{ $latest }}</a>, the latest version.
                </p>
            @endif

            <p class="eyebrow"><span class="label">{{ $group }}</span><span class="bar"></span></p>
            <h1 class="docs-title">{{ $heading }}</h1>

            <div class="doc-prose">{!! $html !!}</div>

            <p class="docs-edit"><a href="{{ $edit }}" target="_blank" rel="noopener">Edit this page on GitHub</a></p>

            <div class="docs-pager">
                @if ($previous)
                    <a href="{{ $previous['url'] }}" class="prev"><span class="label">Previous</span>{{ $previous['label'] }}</a>
                @endif
                @if ($next)
                    <a href="{{ $next['url'] }}" class="next"><span class="label">Next</span>{{ $next['label'] }}</a>
                @endif
            </div>
        </article>

        @if (count($toc) > 1)
            <aside class="docs-toc" aria-label="On this page">
                <p class="label">On this page</p>
                @foreach ($toc as $entry)
                    <a href="#{{ $entry['id'] }}" class="l{{ $entry['level'] }}">{{ $entry['text'] }}</a>
                @endforeach
            </aside>
        @endif
    </div>

    @php(\dobron\BigPipe\BigPipe::page()->call('tutorial/Code', 'highlight'))
@endsection
