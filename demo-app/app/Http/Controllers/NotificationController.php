<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use App\Models\Alert;
use dobron\BigPipe\Poller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return $this->page('notifications.index', [
            'items' => $request->user()->unreadAlerts()->latest('id')->get(),
        ], 'Notifications');
    }

    public function poll(Request $request)
    {
        $unread = $request->user()->unreadAlerts();

        $response = (new AsyncResponse())
            ->setContent('#unread-count', (string) $unread->count())
            ->setContent('#notifications', view('notifications._items', [
                'items' => $unread->latest('id')->get(),
            ])->render());

        if (Poller::requestedId() !== null && ! $unread->exists()) {
            // Nothing new: ask less often.
            $response->call(Poller::requestedId(), 'setInterval', [10000]);
        }

        return $response->send();
    }

    public function simulate(Request $request)
    {
        Alert::create([
            'user_id' => $request->user()->id,
            'title' => fake()->randomElement(['A new comment was added', 'Your order has shipped', 'Someone liked your post']),
        ]);

        return (new AsyncResponse())
            ->call('Toastr', 'success', ['An event happened. The next poll shows it.'])
            ->send();
    }

    public function read(Request $request)
    {
        $request->user()->unreadAlerts()->update(['read_at' => now()]);

        return (new AsyncResponse())
            ->setContent('#unread-count', '0')
            ->setContent('#notifications', view('notifications._items', ['items' => collect()])->render())
            ->send();
    }
}
