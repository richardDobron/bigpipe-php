<?php

namespace App\Pagelets\Tutorial;

use dobron\BigPipe\Pagelet;
use dobron\BigPipe\Poller;

/**
 * A deploy in progress. The poller printed in its content starts with the pagelet, and the server stops it
 * when the deploy is done.
 */
class DeployPagelet extends Pagelet
{
    public const SECONDS = 8;

    protected function content(): string
    {
        (new Poller(route('poller.status'), 2000))
            ->setMaxRequests(20)
            ->start();

        return view('tutorial._deploy', ['progress' => 0])->render();
    }
}
