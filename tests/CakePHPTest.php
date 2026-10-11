<?php

declare(strict_types=1);

use Cake\Cache\Cache;
use Cake\Cache\Engine\NullEngine;
use Cake\Core\Configure;
use Cake\Http\Exception\InvalidCsrfTokenException;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Http\Response;
use Cake\Http\Runner;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\Utility\Security;
use Cake\View\View;
use dobron\BigPipe\CakePHP\AsyncResponse;
use dobron\BigPipe\CakePHP\BigPipePlugin;
use dobron\BigPipe\CakePHP\Middleware\CsrfRecoveryMiddleware;
use dobron\BigPipe\CakePHP\View\Helper\BigPipeHelper;
use dobron\BigPipe\Psr\BigPipeMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @runTestsInSeparateProcesses
 */
class CakePHPTest extends TestCase
{
    protected function setUp(): void
    {
        Configure::write('App.encoding', 'UTF-8');
    }

    public function testPluginAddsTheMiddleware(): void
    {
        $plugin = new BigPipePlugin();
        $queue = $plugin->middleware(new MiddlewareQueue());

        $this->assertSame('BigPipe', $plugin->getName());
        $this->assertCount(1, $queue);
        $queue->rewind();
        $this->assertInstanceOf(BigPipeMiddleware::class, $queue->current());
    }

    public function testResponse(): void
    {
        $response = (new AsyncResponse())->setContent('#a', 'b')->send(422);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertStringStartsWith('for (;;);{"payload":[],"domops":[["setContent","#a"', (string) $response->getBody());
    }

    public function testStreamedWhenRequested(): void
    {
        Router::setRequest(new ServerRequest(['query' => [AsyncResponse::STREAM_PARAM => 1]]));

        $response = (new AsyncResponse())->setContent('#a', 'b')->send();

        $output = '';
        ob_start(static function (string $chunk) use (&$output): string {
            $output .= $chunk;

            return '';
        });
        $response->getBody()->getContents();
        ob_end_clean();

        $this->assertSame(2, substr_count($output, AsyncResponse::STREAM_DELIMITER));
    }

    /**
     * Handles the request with the middleware of the plugin around CsrfProtectionMiddleware.
     */
    private function handle(ServerRequest $request, array $options = [], ?callable $handle = null): ResponseInterface
    {
        Security::setSalt(str_repeat('s', 32));
        // The message of InvalidCsrfTokenException is translated.
        if (!Cache::getConfig('_cake_translations_')) {
            Cache::setConfig('_cake_translations_', ['className' => NullEngine::class]);
        }
        $queue = (new BigPipePlugin($options))->middleware(new MiddlewareQueue([new CsrfProtectionMiddleware()]));
        $handler = new class ($handle ?? static fn () => new Response(['body' => 'saved'])) implements RequestHandlerInterface {
            public function __construct(private $handle)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return ($this->handle)($request);
            }
        };

        return (new Runner())->run($queue, $request, $handler);
    }

    private function ajaxRequest(string $method, string $url, array $cookies = [], array $headers = []): ServerRequest
    {
        $environment = ['REQUEST_METHOD' => $method, 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];

        foreach ($headers as $name => $value) {
            $environment['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
        }

        return new ServerRequest(['url' => $url, 'environment' => $environment, 'cookies' => $cookies]);
    }

    /**
     * @return array{token: string, cookie: string}
     */
    private function tokenOf(ResponseInterface $response): array
    {
        preg_match('~"CSRFToken",\{"token":"([^"]+)"~', (string) $response->getBody(), $matches);

        return ['token' => stripslashes($matches[1]), 'cookie' => $response->getCookie('csrfToken')['value']];
    }

    public function testPluginPlacesTheRecoveryBeforeCsrfProtection(): void
    {
        $queue = (new BigPipePlugin())->middleware(new MiddlewareQueue([new CsrfProtectionMiddleware()]));

        $this->assertSame(
            [CsrfRecoveryMiddleware::class, CsrfProtectionMiddleware::class, BigPipeMiddleware::class],
            array_map('get_class', iterator_to_array($queue))
        );
    }

    public function testPageNamesTheRefreshUrl(): void
    {
        $response = $this->handle(
            new ServerRequest(['url' => '/', 'environment' => ['REQUEST_METHOD' => 'GET']]),
            handle: static fn () => (new AsyncResponse())->send()
        );

        $this->assertMatchesRegularExpression(
            '~\["CSRFToken",\{"token":"[^"]+","header":"X-CSRF-Token","param":null,"refresh":"\\\\/bigpipe\\\\/csrf-token"\}\]~',
            (string) $response->getBody()
        );
    }

    public function testRefreshUrlSendsANewTokenAndItsCookie(): void
    {
        $response = $this->handle($this->ajaxRequest('GET', '/bigpipe/csrf-token'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('"define":[["CSRFToken",{"token":"', (string) $response->getBody());
        $this->assertNotEmpty($this->tokenOf($response)['cookie']);
    }

    public function testExpiredTokenOfAnAjaxRequestIsRetriedWithATokenThatIsAccepted(): void
    {
        $response = $this->handle($this->ajaxRequest('POST', '/save'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('"csrf_refresh":1', (string) $response->getBody());

        ['token' => $token, 'cookie' => $cookie] = $this->tokenOf($response);
        $retried = $this->handle($this->ajaxRequest('POST', '/save', ['csrfToken' => $cookie], ['X-CSRF-Token' => $token]));

        $this->assertSame('saved', (string) $retried->getBody());
    }

    public function testInvalidCookieIsReplaced(): void
    {
        $response = $this->handle($this->ajaxRequest('POST', '/save', ['csrfToken' => base64_encode(str_repeat('x', 56))]));

        $this->assertStringContainsString('"csrf_refresh":1', (string) $response->getBody());
        $this->assertNotSame(base64_encode(str_repeat('x', 56)), $this->tokenOf($response)['cookie']);
    }

    public function testExpiredTokenOfAPageIsNotHandled(): void
    {
        $this->expectException(InvalidCsrfTokenException::class);

        $this->handle(new ServerRequest(['url' => '/save', 'environment' => ['REQUEST_METHOD' => 'POST']]));
    }

    public function testRecoveryCanBeTurnedOff(): void
    {
        $queue = (new BigPipePlugin(['csrf' => ['refresh_uri' => null, 'retry' => false]]))
            ->middleware(new MiddlewareQueue([new CsrfProtectionMiddleware()]));

        $this->assertCount(2, $queue);

        $this->expectException(InvalidCsrfTokenException::class);

        $this->handle($this->ajaxRequest('POST', '/save'), ['csrf' => ['retry' => false]]);
    }

    public function testHelper(): void
    {
        $view = new View();
        $view->loadHelper('BigPipe', ['className' => BigPipeHelper::class]);

        $view->BigPipe->jsmod('Feed', 'init', [1]);
        $view->BigPipe->define('Config', ['id' => 1]);
        $html = $view->BigPipe->render();

        $this->assertStringContainsString('"require":[["Feed","init",[1]]]', $html);
        $this->assertStringContainsString('"define":[["Config",{"id":1}]]', $html);
    }
}
