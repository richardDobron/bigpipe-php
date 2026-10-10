<?php

namespace dobron\BigPipe\Psr;

use dobron\BigPipe\BigPipe;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 middleware (Slim, Mezzio, CakePHP, ...): handles the request with a fresh context, so a
 * worker that serves many requests does not carry the pagelets and modules of one request into the
 * next, and sends the CSRF token of the request to the browser.
 */
class BigPipeMiddleware implements MiddlewareInterface
{
    /** @var null|callable(ServerRequestInterface): ?string */
    private $csrfToken;

    /**
     * @param null|callable(ServerRequestInterface): ?string $csrfToken returns the CSRF token of the
     *        request, e.g. fn ($request) => $request->getAttribute('csrfToken')
     */
    public function __construct(
        ?callable $csrfToken = null,
        private ?string $csrfHeader = 'X-CSRF-TOKEN',
        private ?string $csrfParam = null
    ) {
        $this->csrfToken = $csrfToken;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return BigPipe::withContext(function () use ($request, $handler): ResponseInterface {
            $token = $this->csrfToken === null ? null : ($this->csrfToken)($request);

            if ($token !== null && $token !== '') {
                BigPipe::setCSRFToken($token, $this->csrfHeader, $this->csrfParam);
            }

            return $handler->handle($request);
        });
    }
}
