<?php

namespace dobron\BigPipe\CakePHP\Middleware;

use Cake\Http\Exception\InvalidCsrfTokenException;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\Response;
use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Recovers an expired CSRF token of CsrfProtectionMiddleware, which BigPipePlugin places before it
 * and before RoutingMiddleware: it answers the refresh URL with a new token, and a request of
 * BigPipe rejected with InvalidCsrfTokenException with the new token, so the browser sends the
 * request again, once. Both get the token from a GET through CsrfProtectionMiddleware, which sets a
 * new cookie when the request has none.
 */
class CsrfRecoveryMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CsrfProtectionMiddleware $csrf,
        private ?string $refreshUri = '/bigpipe/csrf-token',
        private bool $retry = true,
        private ?string $header = 'X-CSRF-Token',
        private ?string $param = null
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (
            $this->refreshUri !== null
            && $request->getMethod() === 'GET'
            && $request->getUri()->getPath() === $this->refreshUri
        ) {
            return $this->csrf->process($request, $this->tokenHandler(false));
        }

        if (!$this->retry) {
            return $handler->handle($request);
        }

        try {
            return $handler->handle($request);
        } catch (InvalidCsrfTokenException $e) {
            if ($request->getHeaderLine('X-Requested-With') !== 'XMLHttpRequest') {
                throw $e;
            }

            $request = $request->withMethod('GET')->withParsedBody([]);

            // An invalid cookie is expired by the exception: a new token needs a new cookie.
            $expired = $e->getHeaders()['Set-Cookie'] ?? null;
            if (is_string($expired)) {
                $cookies = $request->getCookieParams();
                unset($cookies[explode('=', $expired, 2)[0]]);
                $request = $request->withCookieParams($cookies);
            }

            return $this->csrf->process($request, $this->tokenHandler(true));
        }
    }

    /**
     * Answers with the token CsrfProtectionMiddleware set for the request, see BigPipe::setCSRFToken().
     */
    private function tokenHandler(bool $retry): RequestHandlerInterface
    {
        return new class ($retry, $this->header, $this->param, $this->refreshUri) implements RequestHandlerInterface {
            public function __construct(
                private bool $retry,
                private ?string $header,
                private ?string $param,
                private ?string $refreshUri
            ) {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $token = (string) $request->getAttribute('csrfToken');
                $refreshUri = $this->refreshUri === null ? null : $request->getAttribute('base', '') . $this->refreshUri;
                $response = new AsyncResponse(new Context());

                if ($this->retry) {
                    $response->retryWithCSRFToken($token, $this->header, $this->param, $refreshUri);
                } else {
                    $response->define(
                        BigPipe::CSRF_TOKEN_MODULE,
                        BigPipe::csrfTokenModule($token, $this->header, $this->param, $refreshUri)
                    );
                }

                $cakeResponse = (new Response(['charset' => 'UTF-8']))->withStringBody($response->buildResponseString());

                foreach (AsyncResponse::headers() as $name => $value) {
                    $cakeResponse = $cakeResponse->withHeader($name, $value);
                }

                return $cakeResponse;
            }
        };
    }
}
