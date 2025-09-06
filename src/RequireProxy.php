<?php

namespace dobron\BigPipe;

class RequireProxy
{
    protected BigPipe $parent;
    protected array $parts = [];
    protected ?int $priority;

    public function __construct(BigPipe $parent, ?int $priority = null)
    {
        $this->parent = $parent;
        $this->priority = $priority;
    }

    public function __call(string $name, array $args)
    {
        if (empty($args)) {
            $this->parts[] = $name;
            return $this;
        }

        if (count($this->parts) === 0) {
            $fragments = [$name];
        } else {
            $fragments = [$this->parts[0], $name];
        }

        if (count($args) === 1 && is_array($args[0])) {
            $finalArgs = $args[0];
        } else {
            $finalArgs = $args;
        }

        $this->parent->require($fragments, $finalArgs, $this->priority);

        return $this->parent;
    }
}
