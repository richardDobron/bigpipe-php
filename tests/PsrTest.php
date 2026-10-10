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
