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
}
