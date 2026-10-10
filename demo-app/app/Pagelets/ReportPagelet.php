<?php

namespace App\Pagelets;

use App\Models\Post;
use dobron\BigPipe\Pagelet;

class ReportPagelet extends Pagelet
{
    protected mixed $fallback = '<p class="text-gray-500">The report is not available right now.</p>';

    protected function content(): string
    {
        Pagelet::sleep(1.2);

        return view('dashboard._report', [
            'posts' => Post::count(),
            'comments' => \App\Models\Comment::count(),
        ])->render();
    }
}
