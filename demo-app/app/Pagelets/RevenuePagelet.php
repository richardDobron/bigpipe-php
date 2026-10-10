<?php

namespace App\Pagelets;

use App\Models\CartLine;
use dobron\BigPipe\Pagelet;

class RevenuePagelet extends Pagelet
{
    protected mixed $fallback = '<p class="text-gray-500">Revenue is not available right now.</p>';

    protected function content(): string
    {
        // A slow API: waiting here does not hold up the other pagelets.
        Pagelet::sleep(0.6);

        $this->call('Dashboard/Chart', 'draw', [[12, 18, 9, 24, 30, 22, 27]]);

        return view('dashboard._revenue', [
            'total' => CartLine::with('product')->get()->sum->total(),
        ])->render();
    }
}
