<?php

namespace dobron\BigPipe;

/**
 * Holds the BigPipe state (pagelets and jsmods) of a single request.
 *
 * Every BigPipe instance created during a request shares the current context, so a nested
 * AsyncResponse or a Pagelet adds its jsmods to the same response. Long-running servers
 * (Octane, FrankenPHP worker mode, RoadRunner, Swoole) must start each request with a fresh
 * context, see BigPipe::setContextResolver() and BigPipe::withContext().
 */
class Context
{
    /** @var array<string, Pagelet> */
    public array $pagelets = [];
    public array $priorities = [];
    public array $jsmods = [
        "require" => [],
    ];

    public function addPagelet(string $id, Pagelet $pagelet): void
    {
        $this->pagelets[$id] = $pagelet;
    }

    public function jsmods(): array
    {
        array_multisort($this->priorities, $this->jsmods['require']);

        return $this->jsmods;
    }

    public function reset(): void
    {
        $this->pagelets = [];
        $this->priorities = [];
        $this->jsmods = [
            "require" => [],
        ];
    }
}
