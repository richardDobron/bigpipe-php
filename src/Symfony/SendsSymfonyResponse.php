<?php

namespace dobron\BigPipe\Symfony;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Turns a response of BigPipe into a response of Symfony. A controller returns either one: the
 * bundle converts the response of BigPipe, see BigPipeSubscriber.
 */
trait SendsSymfonyResponse
{
    /**
     * Whether the current request accepts a streamed response. Reads the request of the request
     * stack, which a worker (RoadRunner, Swoole) does not copy to $_REQUEST.
     */
    public static function isStreamRequested(): bool
    {
        $request = BigPipeBundle::currentRequest();

        if ($request !== null) {
            return $request->query->has(static::STREAM_PARAM) || $request->request->has(static::STREAM_PARAM);
        }

        return parent::isStreamRequested();
    }

    /**
     * The response for Symfony, streamed when the request accepts it, see stream().
     */
    public function send(int $status = 200): Response
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
}
