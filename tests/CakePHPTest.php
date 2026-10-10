<?php

declare(strict_types=1);

use Cake\Core\Configure;
use Cake\Http\MiddlewareQueue;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\View\View;
use dobron\BigPipe\CakePHP\AsyncResponse;
use dobron\BigPipe\CakePHP\BigPipePlugin;
use dobron\BigPipe\CakePHP\View\Helper\BigPipeHelper;
use dobron\BigPipe\Psr\BigPipeMiddleware;
use PHPUnit\Framework\TestCase;

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
