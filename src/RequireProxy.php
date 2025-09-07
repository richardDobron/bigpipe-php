<?php

namespace dobron\BigPipe;

class RequireProxy
{
    /** @var BigPipe */
    protected $parent;
    protected $parts = [];
    protected $args = [];
    protected $priority;

    public function __construct(BigPipe $parent, ?int $priority = null)
    {
        $this->parent = $parent;
        $this->priority = $priority;
    }

    public function __call(string $name, array $args)
    {
        $this->parts[] = $name;

        if (empty($args)) {
            return $this;
        }

        $this->args = $args[0];

        return $this->parent;
    }

    public function __destruct()
    {
        if (!empty($this->parts)) {
            $this->parent->require($this->parts, $this->args, $this->priority);
        }
    }
}
