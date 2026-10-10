<?php

namespace dobron\BigPipe\Symfony;

/**
 * An AsyncResponse a controller returns: `return $response;` or `return $response->send(422);`.
 */
class AsyncResponse extends \dobron\BigPipe\AsyncResponse
{
    use SendsSymfonyResponse;
}
