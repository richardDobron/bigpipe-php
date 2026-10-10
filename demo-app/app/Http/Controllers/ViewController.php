<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * A page that is only a view, e.g. a tutorial: `Route::get('/dialogs', ViewController::class)->defaults('view', ...)`.
 * It answers a page transition like any other page.
 */
class ViewController extends Controller
{
    public function __invoke(Request $request)
    {
        return $this->page($request->route('view'));
    }
}
