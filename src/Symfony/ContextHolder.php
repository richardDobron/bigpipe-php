<?php

namespace dobron\BigPipe\Symfony;

use dobron\BigPipe\Context;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Holds the context of the current request. Symfony resets it between the requests of a worker
 * (FrankenPHP, RoadRunner, Swoole), so the pagelets and modules of one request are not carried into
 * the next.
 */
class ContextHolder implements ResetInterface
{
    private Context $context;

    public function __construct()
    {
        $this->context = new Context();
    }

    public function context(): Context
    {
        return $this->context;
    }

    public function reset(): void
    {
        $this->context = new Context();
    }
}
