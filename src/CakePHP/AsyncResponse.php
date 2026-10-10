<?php

namespace dobron\BigPipe\CakePHP;

/**
 * An AsyncResponse a controller returns: `return $response->send();`.
 */
class AsyncResponse extends \dobron\BigPipe\AsyncResponse
{
    use SendsCakeResponse;
}
