<?php

namespace dobron\BigPipe\Symfony;

/**
 * A DialogResponse a controller returns: `return $response->dialog();`.
 */
class DialogResponse extends \dobron\BigPipe\DialogResponse
{
    use SendsSymfonyResponse;
}
