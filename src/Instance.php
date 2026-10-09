<?php

namespace dobron\BigPipe;

/**
 * A JavaScript object the browser creates from a class once and shares, see JsMods::instance().
 *
 * Passed as an argument of require() or of another instance, it becomes a module transport marker,
 * so the module receives the object itself.
 */
class Instance implements \JsonSerializable
{
    public function __construct(
        protected BigPipe|Pagelet $owner,
        protected string $id
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Calls a method of the instance.
     *
     * @throws \Throwable
     */
    public function call(string $method, array $args = [], ?int $priority = null): static
    {
        $this->owner->require([$this->id, $method], $args, $priority);

        return $this;
    }

    public function jsonSerialize(): array
    {
        return TransportMarker::transportModule($this->id);
    }
}
