@php
    // The top bar stays during page transitions: app.js marks the link of the current page by data-match.
    $links = [
        ['Tutorials', url('/').'#tutorials', '^/(tutorial/|$)'],
        ['Blog', route('posts.index'), '^/app/posts'],
        ['Shop', route('products.index'), '^/app/(shop|cart)'],
        ['Dashboard', route('dashboard'), '^/app/dashboard'],
        ['Profile', route('profile.show'), '^/app/profile'],
        ['Docs', url('/docs'), '^/docs'],
    ];
    $path = '/'.ltrim(request()->path(), '/');
@endphp
<header class="topbar">
    <a href="{{ url('/') }}" class="brand" aria-label="BigPipe">
        @include('partials.logo')
    </a>
    <nav class="links">
        @foreach ($links as [$name, $href, $match])
            <a href="{{ $href }}" @if ($match) data-match="{{ $match }}" @endif
               class="link {{ $match && preg_match('#'.$match.'#', $path) ? 'active' : '' }}">{{ $name }}</a>
        @endforeach
    </nav>
    <div class="end">
        @auth
            <a href="{{ route('notifications.index') }}" class="link" data-match="^/app/notifications">
                Alerts<span id="unread-count" class="pill">{{ auth()->user()->unreadAlerts()->count() }}</span>
            </a>
            <a href="{{ route('cart.show') }}" class="link">
                Cart<span id="cart-badge" class="pill">{{ auth()->user()->cartLines()->sum('quantity') }}</span>
            </a>
        @endauth
        <a href="https://github.com/richardDobron/bigpipe-php" class="link">GitHub</a>
        <button type="button" class="theme-switch" id="theme-switch" aria-label="Switch to the dark theme" title="Dark theme">
            <svg class="sun" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><circle cx="10" cy="10" r="3.4"/><path d="M10 2.5v1.6M10 15.9v1.6M2.5 10h1.6M15.9 10h1.6M4.7 4.7l1.1 1.1M14.2 14.2l1.1 1.1M4.7 15.3l1.1-1.1M14.2 5.8l1.1-1.1"/></svg>
            <svg class="moon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" aria-hidden="true"><path d="M16.2 12.4A6.8 6.8 0 0 1 7.6 3.8a6.8 6.8 0 1 0 8.6 8.6Z"/></svg>
        </button>
    </div>
</header>
