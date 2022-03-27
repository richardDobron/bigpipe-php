<?php

namespace App\Http\Controllers;

use dobron\BigPipe\AsyncResponse;

class RedirectController extends Controller
{
    private $response;

    public function __construct(AsyncResponse $response)
    {
        $this->response = $response;
    }

    public function reload()
    {
        $this->response->reload();

        return response()->json($this->response->getResponse());
    }

    public function reloadDelay()
    {
        $this->response->reload(250);

        return response()->json($this->response->getResponse());
    }

    public function redirect()
    {
        $this->response->redirect('/');

        return response()->json($this->response->getResponse());
    }

    public function redirectDelay()
    {
        $this->response->redirect('/', 500);

        return response()->json($this->response->getResponse());
    }
}
