<?php

namespace dobron\BigPipe\Psr;

use dobron\BigPipe\AsyncResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Turns an AsyncResponse or a DialogResponse into a PSR-7 response:
 *
 *     return $bigPipeResponses->create((new AsyncResponse())->setContent('#a', $html), $request);
 */
class ResponseFactory
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory
    ) {
    }

    /**
     * When the request asks for a streamed response, the body has its parts, but they are sent
     * together: a PSR-7 body is written once the response is complete.
     *
     * @throws \Throwable
     */
    public function create(AsyncResponse $response, ServerRequestInterface $request, int $status = 200): ResponseInterface
    {
        if (static::isStreamRequested($request)) {
            $body = '';
            $response->stream(static function (string $part) use (&$body): void {
                $body .= $part;
            });
        } else {
            $body = $response->buildResponseString();
        }

        $psrResponse = $this->responseFactory->createResponse($status)
            ->withBody($this->streamFactory->createStream($body));

        foreach (AsyncResponse::headers() as $name => $value) {
            $psrResponse = $psrResponse->withHeader($name, $value);
        }

        return $psrResponse;
    }

    /**
     * Whether the request accepts a streamed response, see AsyncResponse::stream().
     */
    public static function isStreamRequested(ServerRequestInterface $request): bool
    {
        $body = $request->getParsedBody();

        return isset($request->getQueryParams()[AsyncResponse::STREAM_PARAM])
            || (is_array($body) && isset($body[AsyncResponse::STREAM_PARAM]));
    }
}
