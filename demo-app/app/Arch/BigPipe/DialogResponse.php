<?php

namespace App\Arch\BigPipe;

use Illuminate\Http\Response;

class DialogResponse extends \dobron\BigPipe\DialogResponse
{
    public function send(int $status = 200): Response
    {
        return response($this->buildResponseString(), $status)
            ->withHeaders(static::headers());
    }
}
