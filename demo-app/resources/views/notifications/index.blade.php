@extends('layouts.app')

@section('content')
    <x-page-head eyebrow="05 / Alerts" title="Notifications">A <code>Poller</code> asks every 4 seconds while this tab is visible. Simulate an event and watch the badge and the list change.</x-page-head>

    <div class="flex gap-3 mb-4">
        <a href="{{ route('notifications.index') }}" ajaxify="{{ route('notifications.simulate') }}" rel="async-post"
           class="px-3 py-2 rounded bg-blue-600 text-white text-sm">Simulate an event</a>
        <a href="{{ route('notifications.index') }}" ajaxify="{{ route('notifications.read') }}" rel="async-post"
           class="px-3 py-2 rounded border border-gray-300 text-sm">Mark all as read</a>
    </div>

    <ul id="notifications" class="space-y-2">@include('notifications._items', ['items' => $items])</ul>

    @php
        (new \dobron\BigPipe\Poller(route('notifications.poll'), 4000))
            ->setMuteWhenIdle(5 * 60 * 1000)
            ->start();
    @endphp
@endsection
