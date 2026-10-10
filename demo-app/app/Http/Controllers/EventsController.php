<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use dobron\BigPipe\TransportMarker;
use Illuminate\Http\Request;

class EventsController extends Controller
{
    private const ARBITER = 'bigpipe-util/dist/core/Arbiter';

    private const PRODUCTS = [['Keyboard', 79.0], ['Mouse', 29.5], ['Monitor', 249.0], ['Headphones', 119.0]];

    public function show(Request $request)
    {
        // A fresh start: no orders, and no last order kept in the browser from an earlier visit.
        $request->session()->forget('event_orders');
        \dobron\BigPipe\BigPipe::page()->call(self::ARBITER, 'clearState', ['ORDER/PLACED']);

        return $this->page('tutorial.events');
    }

    public function order(Request $request)
    {
        $orders = $request->session()->get('event_orders', []);
        [$product, $price] = self::PRODUCTS[count($orders) % count(self::PRODUCTS)];
        $orders[] = $price;
        $request->session()->put('event_orders', $orders);

        // informState: a module that subscribes later is called with the last order right away.
        return (new AsyncResponse())
            ->call(self::ARBITER, 'informState', ['ORDER/PLACED', [
                'id' => 1000 + count($orders),
                'product' => $product,
                'price' => $price,
                'count' => count($orders),
                'total' => array_sum($orders),
            ]])
            ->send();
    }

    /**
     * Adds a widget to the page after the events: it gets the last order as soon as it subscribes.
     */
    public function widget()
    {
        return (new AsyncResponse())
            ->replace('#late-widget', '<div id="late-widget">'.view('tutorial._order-log', ['id' => 'late-log', 'title' => 'Added later'])->render().'</div>')
            ->call('tutorial/OrderLog', 'init', [TransportMarker::element('late-log')])
            ->send();
    }
}
