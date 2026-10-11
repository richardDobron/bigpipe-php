<?php

declare(strict_types=1);

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Psr\BigPipeMiddleware;
use dobron\BigPipe\Psr\ResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @runTestsInSeparateProcesses
 */
class PsrTest extends TestCase
{
    private function handler(callable $handle): RequestHandlerInterface
    {
        return new class ($handle) implements RequestHandlerInterface {
            public function __construct(private $handle)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return ($this->handle)($request);
            }
        };
    }

    private function responses(): ResponseFactory
    {
        $factory = new Psr17Factory();

        return new ResponseFactory($factory, $factory);
    }

    public function testMiddlewareHandlesTheRequestWithAFreshContext(): void
    {
        BigPipe::page()->call('Outer');
        $outer = BigPipe::context();

        $response = (new BigPipeMiddleware())->process(
            (new Psr17Factory())->createServerRequest('GET', '/'),
            $this->handler(function (ServerRequestInterface $request) use ($outer) {
                $this->assertNotSame($outer, BigPipe::context());

                return $this->responses()->create((new AsyncResponse())->call('Inner'), $request);
            })
        );

        $this->assertStringContainsString('"require":[["Inner"]]', (string) $response->getBody());
        $this->assertSame($outer, BigPipe::context());
        $this->assertSame([['Outer']], BigPipe::jsmods()['require']);
    }

    public function testMiddlewareSendsTheCsrfToken(): void
    {
        $middleware = new BigPipeMiddleware(fn (ServerRequestInterface $request) => $request->getAttribute('csrf'));

        $response = $middleware->process(
            (new Psr17Factory())->createServerRequest('GET', '/')->withAttribute('csrf', 'secret'),
            $this->handler(fn ($request) => $this->responses()->create(new AsyncResponse(), $request))
        );

        $this->assertStringContainsString(
            '"define":[["CSRFToken",{"token":"secret","header":"X-CSRF-TOKEN","param":null}]]',
            (string) $response->getBody()
        );
    }

    private function recovering(bool $retry = true): BigPipeMiddleware
    {
        return new BigPipeMiddleware(
            fn (ServerRequestInterface $request) => $request->getAttribute('csrf'),
            csrfRefreshUri: '/bigpipe/csrf-token',
            responses: $this->responses(),
            csrfRetry: $retry
        );
    }

    private function rejectingHandler(): RequestHandlerInterface
    {
        return $this->handler(fn () => (new Psr17Factory())->createResponse(419));
    }

    private function ajaxRequest(string $method, string $uri): ServerRequestInterface
    {
        return (new Psr17Factory())->createServerRequest($method, $uri)
            ->withAttribute('csrf', 'n3w')
            ->withHeader('X-Requested-With', 'XMLHttpRequest');
    }

    public function testMiddlewareNamesTheRefreshUrl(): void
    {
        $response = $this->recovering()->process(
            (new Psr17Factory())->createServerRequest('GET', '/')->withAttribute('csrf', 'secret'),
            $this->handler(fn ($request) => $this->responses()->create(new AsyncResponse(), $request))
        );

        $this->assertStringContainsString(
            '"define":[["CSRFToken",{"token":"secret","header":"X-CSRF-TOKEN","param":null,"refresh":"\/bigpipe\/csrf-token"}]]',
            (string) $response->getBody()
        );
    }

    public function testMiddlewareAnswersTheRefreshUrl(): void
    {
        $response = $this->recovering()->process(
            $this->ajaxRequest('GET', '/bigpipe/csrf-token'),
            $this->handler(fn () => $this->fail('The refresh URL is answered by the middleware.'))
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('"define":[["CSRFToken",{"token":"n3w"', (string) $response->getBody());
    }

    public function testRejectedTokenOfAnAjaxRequestIsRetried(): void
    {
        $response = $this->recovering()->process($this->ajaxRequest('POST', '/save'), $this->rejectingHandler());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('"csrf_refresh":1', (string) $response->getBody());
        $this->assertStringContainsString('"define":[["CSRFToken",{"token":"n3w"', (string) $response->getBody());
    }

    public function testRejectedTokenOfAPageIsNotHandled(): void
    {
        $request = (new Psr17Factory())->createServerRequest('POST', '/save')->withAttribute('csrf', 'n3w');

        $this->assertSame(419, $this->recovering()->process($request, $this->rejectingHandler())->getStatusCode());
    }

    public function testRetryCanBeTurnedOff(): void
    {
        $response = $this->recovering(retry: false)->process($this->ajaxRequest('POST', '/save'), $this->rejectingHandler());

        $this->assertSame(419, $response->getStatusCode());
    }

    public function testResponse(): void
    {
        $response = $this->responses()->create(
            (new AsyncResponse())->setContent('#a', 'b'),
            (new Psr17Factory())->createServerRequest('GET', '/'),
            422
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertStringStartsWith('for (;;);{"payload":[],"domops":[["setContent","#a"', (string) $response->getBody());
    }

    public function testStreamedResponseWhenRequested(): void
    {
        $request = (new Psr17Factory())->createServerRequest('POST', '/')
            ->withParsedBody([AsyncResponse::STREAM_PARAM => 1]);

        $body = (string) $this->responses()->create((new AsyncResponse())->setContent('#a', 'b'), $request)->getBody();

        $this->assertSame(2, substr_count($body, AsyncResponse::STREAM_DELIMITER));
    }
}
