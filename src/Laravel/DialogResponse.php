<?php

namespace dobron\BigPipe\Laravel;

use Illuminate\Contracts\Support\Responsable;

/**
 * A DialogResponse a controller returns: `return $response->dialog();`.
 */
class DialogResponse extends \dobron\BigPipe\DialogResponse implements Responsable
{
    use SendsLaravelResponse;
}
