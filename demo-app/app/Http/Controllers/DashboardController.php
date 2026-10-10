<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use App\Pagelets\ReportPagelet;
use dobron\BigPipe\BigPipe;

class DashboardController extends Controller
{
    public function __invoke()
    {
        // The slow parts wait without blocking each other: the page takes as long as the slowest.
        BigPipe::setParallel(true);

        return $this->streamedPage('dashboard', [], 'Dashboard');
    }

    public function report()
    {
        return (new AsyncResponse())
            ->pagelet(new ReportPagelet())
            ->send();
    }
}
