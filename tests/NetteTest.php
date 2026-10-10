<?php

declare(strict_types=1);

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\Nette\BigPipeResponse;
use dobron\BigPipe\Nette\DI\BigPipeExtension;
use dobron\BigPipe\Nette\Latte\BigPipeLatteExtension;
use Latte\Engine;
use Latte\Loaders\StringLoader;
use Nette\Bootstrap\Configurator;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\UrlScript;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class NetteTest extends TestCase
{
    private function render(Engine $latte, string $template, array $params = []): string
    {
        $latte->setLoader(new StringLoader());

        return $latte->renderToString($template, $params);
    }

    private function send(BigPipeResponse $response, array $query = []): string
    {
        ob_start();
        $response->send(new Request(new UrlScript('http://localhost/'), $query), new Response());

        return ob_get_clean();
    }

    public function testLatteFunctions(): void
    {
        $latte = new Engine();
        $latte->addExtension(new BigPipeLatteExtension());

        $html = $this->render($latte, "{jsmod('Feed', 'init', [\$id])}{jsdefine('Config', [id: \$id])}{bigpipe()}", ['id' => 1]);

        $this->assertStringStartsWith('<script>', $html);
        $this->assertStringContainsString('"require":[["Feed","init",[1]]]', $html);
        $this->assertStringContainsString('"define":[["Config",{"id":1}]]', $html);
    }

    public function testExtensionRegistersTheLatteFunctions(): void
    {
        $tempDir = sys_get_temp_dir() . '/bigpipe-nette-test';
        @mkdir($tempDir, 0777, true);

        $configurator = new Configurator();
        $configurator->setTempDirectory($tempDir);
        $configurator->addStaticParameters(['wwwDir' => $tempDir]);
        $configurator->defaultExtensions['bigpipe'] = BigPipeExtension::class;
        $container = $configurator->createContainer();

        $latte = $container->getByType(\Nette\Bridges\ApplicationLatte\LatteFactory::class)->create();

        $this->assertStringContainsString('<script>', $this->render($latte, '{bigpipe()}'));
    }

    public function testResponse(): void
    {
        $body = $this->send(new BigPipeResponse((new AsyncResponse())->setContent('#a', 'b')));

        $this->assertStringStartsWith('for (;;);{"payload":[],"domops":[["setContent","#a"', $body);
    }

    public function testStreamedWhenRequested(): void
    {
        $output = '';
        ob_start(static function (string $chunk) use (&$output): string {
            $output .= $chunk;

            return '';
        });
        (new BigPipeResponse((new AsyncResponse())->setContent('#a', 'b')))->send(
            new Request(new UrlScript('http://localhost/'), [AsyncResponse::STREAM_PARAM => '1']),
            new Response()
        );
        ob_end_clean();

        $this->assertSame(2, substr_count($output, AsyncResponse::STREAM_DELIMITER));
    }
}
