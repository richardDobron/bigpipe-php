<?php

namespace App\Arch\BigPipe;

use Illuminate\Http\Response;

class AsyncResponse extends \dobron\BigPipe\AsyncResponse
{
    public function send(int $status = 200): Response
    {
        return response($this->buildResponseString(), $status)
            ->withHeaders(static::headers());
    }
}
