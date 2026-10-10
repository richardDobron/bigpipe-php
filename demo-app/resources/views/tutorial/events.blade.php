@extends('layout')

@section('header', 'Events')

@section('description')
    Arbiter is the event system of the browser part. Modules subscribe to an event without knowing about each other,
    and any response can inform it: one call from PHP, and every widget that listens reacts.
@endsection

@section('bullet-description')
    <ul role="list">
        <li><div><h3>subscribe(event, callback)</h3><p>In a module: returns a subscription with remove().</p></div></li>
        <li><div><h3>inform(event, data)</h3><p>Calls every subscriber of the event with the data.</p></div></li>
        <li><div><h3>informState(event, data)</h3><p>Like inform, and a later subscriber is called with the last data right away.</p></div></li>
        <li><div><h3>clearState(event)</h3><p>Forgets the last data of the event.</p></div></li>
    </ul>
@endsection

@section('code', <<<'CODE'
// PHP: one call informs every module that listens
return (new AsyncResponse())
    ->call('bigpipe-util/dist/core/Arbiter', 'informState', [
        'ORDER/PLACED',
        ['id' => 1004, 'product' => 'Mouse', 'count' => 4],
    ])
    ->send();

// JavaScript: in any module
new Arbiter().subscribe('ORDER/PLACED', order => {
    badge.textContent = order.count;
});
CODE
)

@php(\dobron\BigPipe\BigPipe::page()->call('tutorial/OrderBadge', 'init', [\dobron\BigPipe\TransportMarker::element('order-badge')]))
@php(\dobron\BigPipe\BigPipe::page()->call('tutorial/OrderLog', 'init', [\dobron\BigPipe\TransportMarker::element('order-log')]))

@section('examples')
    <x-example class="wide" title="One event, independent widgets" caption="Place an order: the response informs ORDER/PLACED, and the badge and the log update on their own. Add a widget after some orders: it subscribes, and gets the last order at once.">
        <div class="events">
            <div class="row" style="justify-content:space-between;align-items:center">
                <a href="{{ url()->current() }}" ajaxify="{{ route('events.order') }}" rel="async-post" class="btn sm">Place an order</a>
                <a href="{{ url()->current() }}" ajaxify="{{ route('events.widget') }}" rel="async-post" class="btn ghost sm">Add a widget</a>
            </div>
            <div class="widgets">
                <div id="order-badge" class="widget badge-widget">
                    <div class="widget-head"><strong>Cart</strong><span class="pl-tag">tutorial/OrderBadge</span></div>
                    <p><span class="count">0</span> <span class="unit">orders</span> · <span class="total">$0.00</span></p>
                </div>
                @include('tutorial._order-log', ['id' => 'order-log', 'title' => 'Activity'])
                <div id="late-widget" class="widget is-placeholder"><p>A widget added later shows up here.</p></div>
            </div>
        </div>
    </x-example>
@endsection
