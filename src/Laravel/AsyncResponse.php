<?php

namespace dobron\BigPipe\Laravel;

use Illuminate\Contracts\Support\Responsable;

/**
 * An AsyncResponse a controller returns: `return $response;` or `return $response->send(422);`.
 */
class AsyncResponse extends \dobron\BigPipe\AsyncResponse implements Responsable
{
    use SendsLaravelResponse;
}
