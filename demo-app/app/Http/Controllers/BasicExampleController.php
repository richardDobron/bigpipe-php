<?php

namespace App\Http\Controllers;

use dobron\BigPipe\AsyncResponse;
use Illuminate\Http\Request;

class BasicExampleController extends Controller
{
    private $response;

    public function __construct(AsyncResponse $response)
    {
        $this->response = $response;
    }

    public function statsPanel()
    {
        $this->response->setContent(
            '#box-stats',
            view('partials.stats')
        );

        return $this->response->send();
    }

    public function showPhoneNumber(Request $request)
    {
        $phoneNumbers = [
            1 => '+4131359771081',
            2 => '+4219104783211',
        ];

        $this->response->replace(
            '',
            $phoneNumbers[$request->get('id')]
        );

        return $this->response->send();
    }

    public function loadImage()
    {
        $this->response->bigPipe()->require(
            "require('tutorial/Image').set()",
            [
                'https://upload.wikimedia.org/wikipedia/commons/9/9a/Laravel.svg',
            ]
        );

        return $this->response->send();
    }
}
