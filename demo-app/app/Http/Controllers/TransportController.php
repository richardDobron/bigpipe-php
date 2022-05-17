<?php

namespace App\Http\Controllers;

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\TransportMarker;
use Illuminate\Http\Request;

class TransportController extends Controller
{
    private $response;

    public function __construct(AsyncResponse $response)
    {
        $this->response = $response;
    }

    public function collection(Request $request)
    {
        $this->response->bigPipe()->require("require('tutorial/Collections').setup()", [
            TransportMarker::transportElement('data-box'),
            TransportMarker::transportMap([
                ['Jack', 20],
                ['Alan', 34],
                ['Bill', 10],
                ['Sam', 9]
            ]),
            $this->response->transport()->transportSet([
                'a', 'b',
                'c', 'c', 'c',
            ]),
        ]);

        return $this->response->send();
    }
}
