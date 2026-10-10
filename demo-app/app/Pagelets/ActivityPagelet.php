<?php

namespace App\Pagelets;

use App\Models\Comment;
use dobron\BigPipe\Pagelet;

class ActivityPagelet extends Pagelet
{
    // Displayed after the revenue.
    protected int $phase = 1;

    protected function content(): string
    {
        Pagelet::sleep(0.9);

        return view('dashboard._activity', [
            'comments' => Comment::with('author', 'post')->latest('id')->limit(6)->get(),
        ])->render();
    }
}
