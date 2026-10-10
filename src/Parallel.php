<?php

namespace dobron\BigPipe;

/**
 * Renders pagelets concurrently with Fibers: while a pagelet waits for something, see await(), the
 * others go on. A pagelet is sent when it is rendered, but never before the pagelets of a lower
 * phase, which the browser would not wait for.
 *
 * @internal use BigPipe::setParallel() and Pagelet::await()
 */
final class Parallel
{
    private static ?ParallelTask $running = null;

    public static function isAvailable(): bool
    {
        return class_exists('Fiber');
    }

    /**
     * Renders the pagelets that $take returns, until it returns null and all are rendered, and calls
     * $emit with each pagelet and its data. $take is asked again after every round, for the pagelets
     * created while others were rendered.
     *
     * @param callable(): ?Pagelet $take
     * @param callable(Pagelet, array): void $emit
     * @throws \Throwable
     */
    public static function run(callable $take, callable $emit): void
    {
        /** @var ParallelTask[] $tasks */
        $tasks = [];

        while (true) {
            while (($pagelet = $take()) !== null) {
                $tasks[] = new ParallelTask($pagelet);
            }

            if (!$tasks) {
                return;
            }

            $progressed = false;

            foreach ($tasks as $task) {
                if (!$task->done && self::step($task)) {
                    $progressed = true;
                }
            }

            usort($tasks, static fn (ParallelTask $a, ParallelTask $b): int => $a->phase <=> $b->phase);

            $blocking = PHP_INT_MAX;
            foreach ($tasks as $task) {
                if (!$task->done) {
                    $blocking = min($blocking, $task->phase);
                }
            }

            foreach ($tasks as $key => $task) {
                if ($task->done && $task->phase <= $blocking) {
                    unset($tasks[$key]);
                    $emit($task->pagelet, $task->data);
                    $progressed = true;
                }
            }

            if (!$progressed) {
                self::pause(self::streamsOf($tasks));
            }
        }
    }

    /**
     * Waits for $ready to return something else than null, and returns it. In a pagelet rendered
     * in parallel the other pagelets go on meanwhile; anywhere else it blocks.
     *
     * @param callable(): mixed $ready
     * @param resource[] $streams streams to wait for, instead of polling $ready on a timer
     * @throws \Throwable
     */
    public static function await(callable $ready, array $streams = [], ?float $timeout = null): mixed
    {
        $deadline = $timeout === null ? null : microtime(true) + $timeout;
        $task = self::$running;

        if ($task === null) {
            while (($result = $ready()) === null) {
                if ($deadline !== null && microtime(true) >= $deadline) {
                    throw new \RuntimeException('Timed out waiting.');
                }

                self::pause($streams);
            }

            return $result;
        }

        if (ob_get_level() !== $task->level + 1) {
            throw new \LogicException(
                'A pagelet can wait only where its content is rendered, not inside of an output buffer'
                . ' or a template: wait first, then render.'
            );
        }

        $buffered = (string) ob_get_clean();

        try {
            return \Fiber::suspend(new ParallelWait($ready, $streams, $deadline));
        } finally {
            ob_start();
            echo $buffered;
        }
    }

    private static function step(ParallelTask $task): bool
    {
        $input = null;
        $exception = null;

        if ($task->fiber !== null) {
            $wait = $task->wait;

            try {
                $input = ($wait->ready)();
            } catch (\Throwable $throwable) {
                $exception = $throwable;
            }

            if ($exception === null && $input === null) {
                if ($wait->deadline === null || microtime(true) < $wait->deadline) {
                    return false;
                }

                $exception = new \RuntimeException('Timed out waiting.');
            }
        }

        $outer = Pagelet::swapRendering($task->rendering);
        $previous = self::$running;
        self::$running = $task;

        try {
            if ($task->fiber === null) {
                $task->level = ob_get_level();
                $task->fiber = new \Fiber(static fn (): array => $task->pagelet->renderData());
                $wait = $task->fiber->start();
            } elseif ($exception !== null) {
                $wait = $task->fiber->throw($exception);
            } else {
                $wait = $task->fiber->resume($input);
            }
        } finally {
            $task->rendering = Pagelet::swapRendering($outer);
            self::$running = $previous;
        }

        if ($task->fiber->isTerminated()) {
            $task->done = true;
            $task->data = $task->fiber->getReturn();
            $task->wait = null;
        } else {
            $task->wait = $wait;
        }

        return true;
    }

    /**
     * @param ParallelTask[] $tasks
     * @return resource[]
     */
    private static function streamsOf(array $tasks): array
    {
        $streams = [];

        foreach ($tasks as $task) {
            if (!$task->done && $task->wait !== null) {
                array_push($streams, ...$task->wait->streams);
            }
        }

        return $streams;
    }

    /**
     * @param resource[] $streams
     */
    private static function pause(array $streams): void
    {
        if ($streams) {
            $read = $streams;
            $write = $except = null;

            @stream_select($read, $write, $except, 0, 10000);

            return;
        }

        usleep(1000);
    }
}

/**
 * @internal
 */
final class ParallelWait
{
    /** @var callable(): mixed */
    public $ready;

    /**
     * @param callable(): mixed $ready
     * @param resource[] $streams
     */
    public function __construct(callable $ready, public array $streams, public ?float $deadline)
    {
        $this->ready = $ready;
    }
}

/**
 * @internal
 */
final class ParallelTask
{
    public ?\Fiber $fiber = null;
    public ?ParallelWait $wait = null;
    public bool $done = false;
    public array $data = [];
    public array $rendering = [];
    public int $level = 0;
    public int $phase;

    public function __construct(public Pagelet $pagelet)
    {
        $this->phase = $pagelet->getPhase();
    }
}
