<?php

namespace dobron\BigPipe\Psr;

use Closure;
use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 middleware (Slim, Mezzio, CakePHP, ...): handles the request with a fresh context, so a
 * worker that serves many requests does not carry the pagelets and modules of one request into the
 * next, and sends the CSRF token of the request to the browser.
 *
 * With a ResponseFactory, it also recovers an expired token: it answers $csrfRefreshUri with a new
 * token, and a request of BigPipe the application rejected with 419 with the new token, so the
 * browser sends the request again, once.
 */
class BigPipeMiddleware implements MiddlewareInterface
{
    /** The status a rejected CSRF token is answered with, see AsyncRequest of bigpipe-util. */
    public const CSRF_EXPIRED_STATUS = 419;

    /** @var null|callable(ServerRequestInterface): ?string */
    private $csrfToken;

    /**
     * @param null|callable(ServerRequestInterface): ?string $csrfToken returns the CSRF token of the
     *        request, e.g. fn ($request) => $request->getAttribute('csrfToken')
     * @param null|string|Closure(ServerRequestInterface): string $csrfRefreshUri the URL the browser
     *        gets a new token from, or a closure that returns it for the request
     * @param ResponseFactory|null $responses answers the refresh URL and a rejected token, without it
     *        neither is answered
     * @param bool $csrfRetry answers a request of BigPipe rejected with 419 with the new token
     */
    public function __construct(
        ?callable $csrfToken = null,
        private ?string $csrfHeader = 'X-CSRF-TOKEN',
        private ?string $csrfParam = null,
        private string|Closure|null $csrfRefreshUri = null,
        private ?ResponseFactory $responses = null,
        private bool $csrfRetry = true
    ) {
        $this->csrfToken = $csrfToken;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $this->csrfToken === null ? null : ($this->csrfToken)($request);
        $token = $token === '' ? null : $token;
        $refreshUri = $this->csrfRefreshUri instanceof Closure ? ($this->csrfRefreshUri)($request) : $this->csrfRefreshUri;

        if (
            $token !== null
            && $refreshUri !== null
            && $this->responses !== null
            && $request->getMethod() === 'GET'
            && $request->getUri()->getPath() === parse_url($refreshUri, PHP_URL_PATH)
        ) {
            $response = (new AsyncResponse(new Context()))->define(
                BigPipe::CSRF_TOKEN_MODULE,
                BigPipe::csrfTokenModule($token, $this->csrfHeader, $this->csrfParam, $refreshUri)
            );

            return $this->responses->create($response, $request);
        }

        $response = BigPipe::withContext(function () use ($request, $handler, $token, $refreshUri): ResponseInterface {
            if ($token !== null) {
                BigPipe::setCSRFToken($token, $this->csrfHeader, $this->csrfParam, $refreshUri);
            }

            return $handler->handle($request);
        });

        if (
            $token !== null
            && $this->csrfRetry
            && $this->responses !== null
            && $response->getStatusCode() === self::CSRF_EXPIRED_STATUS
            && $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest'
        ) {
            $retry = (new AsyncResponse(new Context()))
                ->retryWithCSRFToken($token, $this->csrfHeader, $this->csrfParam, $refreshUri);

            return $this->responses->create($retry, $request);
        }

        return $response;
    }
}
