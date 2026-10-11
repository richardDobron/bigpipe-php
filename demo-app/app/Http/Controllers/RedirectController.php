<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;

class RedirectController extends Controller
{
    public function reload()
    {
        return (new AsyncResponse())->reload()->send();
    }

    public function reloadDelay()
    {
        return (new AsyncResponse())->reload(250)->send();
    }

    public function redirect()
    {
        return (new AsyncResponse())->redirect('/')->send();
    }

    public function redirectDelay()
    {
        return (new AsyncResponse())->redirect('/', 500)->send();
    }

    /**
     * Replaces the entry of this page in the history: the back button does not return to it.
     */
    public function redirectReplace()
    {
        return (new AsyncResponse())->redirect('/', replace: true)->send();
    }

    /**
     * Loads the page with a page transition: the layout stays, only the canvas is replaced.
     */
    public function redirectTransition()
    {
        return (new AsyncResponse())->redirect('/', transition: true)->send();
    }
}
