<?php

namespace dobron\BigPipe\CakePHP;

/**
 * A DialogResponse a controller returns: `return $response->dialog()->send();`.
 */
class DialogResponse extends \dobron\BigPipe\DialogResponse
{
    use SendsCakeResponse;
}
