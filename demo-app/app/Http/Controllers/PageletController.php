<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use App\Pagelets\Tutorial\DeployPagelet;
use App\Pagelets\Tutorial\SlowPagelet;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Poller;
use Illuminate\Http\Request;

class PageletController extends Controller
{
    public function pagelets(Request $request)
    {
        $parallel = $request->query('parallel') !== '0';

        BigPipe::setParallel($parallel);
        // The failing pagelet is part of the example: its exception is not an error of the demo.
        BigPipe::setErrorHandler(fn () => null);

        return $this->streamedPage('tutorial.pagelets', ['parallel' => $parallel]);
    }

    public function refresh(string $id)
    {
        abort_unless(in_array($id, ['profile', 'feed', 'stats'], true), 404);

        return (new AsyncResponse())
            ->refreshPagelet(new SlowPagelet($id))
            ->send();
    }

    public function lazy(string $id)
    {
        abort_unless(in_array($id, ['comments', 'related'], true), 404);

        return (new AsyncResponse())
            ->pagelet(new SlowPagelet($id))
            ->send();
    }

    /**
     * Starts a deploy: the pagelet of the response shows it and starts a poller for its progress.
     */
    public function deploy(Request $request)
    {
        $request->session()->put('deploy_started_at', microtime(true));

        return (new AsyncResponse())
            ->pagelet(new DeployPagelet(), '#deploy')
            ->send();
    }

    /**
     * The endpoint of the poller: the progress, and the poller is stopped when the deploy is done.
     */
    public function deployStatus(Request $request)
    {
        $started = $request->session()->get('deploy_started_at');
        $progress = $started ? min(100, (int) ((microtime(true) - $started) / DeployPagelet::SECONDS * 100)) : 0;

        $response = (new AsyncResponse())
            ->setContent('#deploy-progress', view('tutorial._deploy-progress', ['progress' => $progress])->render());

        if ($progress >= 100 && Poller::requestedId() !== null) {
            $request->session()->forget('deploy_started_at');

            $response
                ->call(Poller::requestedId(), 'stop')
                ->call('Toastr', 'success', ['Deployed. The server stopped the poller.']);
        }

        return $response->send();
    }

    public function quote(Request $request)
    {
        $quote = $this->quoteData($request);
        $html = view('tutorial._quote', $quote)->render();

        $response = new AsyncResponse();

        return ($quote['mode'] === 'replace' ? $response->replace('#quote', $html) : $response->morph('#quote', $html))
            ->send();
    }

    /**
     * @return array{plan: string, seats: string, coupon: string, mode: string, total: ?float, error: ?string}
     */
    public static function quoteData(?Request $request = null): array
    {
        $plan = $request?->input('plan') === 'team' ? 'team' : 'starter';
        $seats = (string) ($request?->input('seats') ?? '3');
        $coupon = (string) ($request?->input('coupon') ?? '');
        $mode = $request?->input('mode') === 'replace' ? 'replace' : 'morph';

        $error = null;
        $total = null;

        if (! ctype_digit($seats) || (int) $seats < 1) {
            $error = 'Enter a number of seats.';
        } elseif ((int) $seats > 50) {
            $error = 'More than 50 seats? Talk to us for a custom plan.';
        } else {
            $total = (int) $seats * ($plan === 'team' ? 12 : 6);

            if (strtoupper(trim($coupon)) === 'PIPE20') {
                $total *= 0.8;
            }
        }

        return compact('plan', 'seats', 'coupon', 'mode', 'total', 'error');
    }
}
