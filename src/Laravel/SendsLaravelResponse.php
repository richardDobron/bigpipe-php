<?php

namespace dobron\BigPipe\Laravel;

use Illuminate\Container\Container;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Turns a response of BigPipe into a response of Laravel, so a controller can return it.
 */
trait SendsLaravelResponse
{
    /**
     * Whether the current request accepts a streamed response. Reads the request of the container,
     * which long-running servers (Octane) do not copy to $_REQUEST.
     */
    public static function isStreamRequested(): bool
    {
        $container = Container::getInstance();

        if ($container->bound('request')) {
            return $container->make('request')->has(static::STREAM_PARAM);
        }

        return parent::isStreamRequested();
    }

    /**
     * The response for Laravel, streamed when the request accepts it, see stream().
     */
    public function send(int $status = 200): SymfonyResponse
    {
        if (static::isStreamRequested()) {
            return new StreamedResponse(
                fn () => $this->stream(),
                $status,
                static::headers() + ['X-Accel-Buffering' => 'no']
            );
        }

        return new Response($this->buildResponseString(), $status, static::headers());
    }

    /**
     * @param \Illuminate\Http\Request $request
     */
    public function toResponse($request): SymfonyResponse
    {
        return $this->send();
    }
}
