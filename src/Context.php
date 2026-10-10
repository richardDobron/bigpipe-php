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

    /**
     * Content Security Policy nonce of the request, added to the inline scripts BigPipe renders.
     * Unlike the pagelets and jsmods, reset() keeps it: it belongs to the whole request.
     */
    public ?string $nonce = null;

    /**
     * Whether pagelets are filled in by the browser (true) or rendered in their placeholders, for
     * crawlers and browsers without JavaScript. Like the nonce, reset() keeps it.
     */
    public bool $pipelining = true;

    /**
     * Whether the pagelets of a page are rendered concurrently, see BigPipe::setParallel(). Like the
     * nonce, reset() keeps it.
     */
    public bool $parallel = false;

    /**
     * The last phase of the pagelets that make the page interactive, see BigPipe::setTtiPhase().
     * Like the nonce, reset() keeps it.
     */
    public ?int $ttiPhase = null;

    protected int $nodeIds = 0;

    /**
     * Returns an element id unique for the page, also across its AsyncRequests, which send the
     * __req counter. Unlike the pagelets and jsmods, reset() keeps the counter.
     */
    public function nextNodeId(): string
    {
        return 'u_' . intval($_REQUEST['__req'] ?? 0) . '_' . $this->nodeIds++;
    }

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
