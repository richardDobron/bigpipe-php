<?php

declare(strict_types=1);

use dobron\BigPipe\AsyncResponse as CoreAsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Symfony\AsyncResponse;
use dobron\BigPipe\Symfony\BigPipeBundle;
use dobron\BigPipe\Symfony\ContextHolder;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class BigPipeSymfonyTestKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct(private array $bigPipeConfig = [])
    {
        parent::__construct('test', false);
    }

    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new TwigBundle(), new BigPipeBundle()];
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/bigpipe-symfony-test/' . self::sourceHash() . '/' . md5(serialize($this->bigPipeConfig)) . '/cache';
    }

    /**
     * A container compiled for other sources of the bundle or of this kernel is never used.
     */
    private static function sourceHash(): string
    {
        $dir = dirname(__DIR__) . '/src/Symfony';
        $files = [...glob("$dir/*.php"), ...glob("$dir/*/*.php"), __FILE__];

        return md5(implode('', array_map('md5_file', $files)));
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/bigpipe-symfony-test/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'secret',
            'test' => true,
            'csrf_protection' => true,
            'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'],
        ]);
        $container->extension('bigpipe', $this->bigPipeConfig);
        // The rejected requests of the tests are not logged to stderr.
        $container->services()->set('logger', NullLogger::class);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('async', '/async')->controller('kernel::asyncAction');
        $routes->add('core', '/core')->controller('kernel::coreAction');
        $routes->add('page', '/page')->controller('kernel::pageAction');
        $routes->add('save', '/save')->controller('kernel::saveAction')->methods(['POST']);
    }

    public function asyncAction(): AsyncResponse
    {
        return (new AsyncResponse())->setContent('#a', 'b');
    }

    public function coreAction(): CoreAsyncResponse
    {
        return (new CoreAsyncResponse())->setContent('#a', 'b');
    }

    public function pageAction(): Response
    {
        return new Response($this->twig()->createTemplate('{{ bigpipe() }}')->render());
    }

    public function saveAction(): Response
    {
        throw new HttpException(419, 'CSRF token expired.');
    }

    public function twig(): \Twig\Environment
    {
        return $this->getContainer()->get('test.service_container')->get('twig');
    }
}

/**
 * @runTestsInSeparateProcesses
 */
class SymfonyTest extends TestCase
{
    private function kernel(array $config = []): BigPipeSymfonyTestKernel
    {
        $kernel = new BigPipeSymfonyTestKernel($config);
        $kernel->boot();

        return $kernel;
    }

    public function testContextIsResetBetweenRequests(): void
    {
        $kernel = $this->kernel();
        $holder = $kernel->getContainer()->get(ContextHolder::class);
        $context = BigPipe::context();

        $this->assertSame($holder->context(), $context);

        $kernel->getContainer()->get('services_resetter')->reset();

        $this->assertNotSame($context, BigPipe::context());
    }

    public function testTwigFunctions(): void
    {
        $html = $this->kernel()->twig()->createTemplate(<<<'TWIG'
{{ bigpipe_jsmod('Feed', 'init', [id]) }}
{{ bigpipe_define('Config', {id: id}) }}
{{ bigpipe() }}
TWIG)->render(['id' => 1]);

        $this->assertStringContainsString('"require":[["Feed","init",[1]]]', $html);
        $this->assertStringContainsString('"define":[["Config",{"id":1}]]', $html);
        $this->assertStringStartsWith("\n\n<script>", $html);
    }

    public function testControllerReturnsAsyncResponse(): void
    {
        $response = $this->kernel()->handle(Request::create('/async'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('for (;;);{"payload":[],"domops":[["setContent","#a"', $response->getContent());
    }

    public function testControllerReturnsCoreAsyncResponse(): void
    {
        $response = $this->kernel()->handle(Request::create('/core'));

        $this->assertStringStartsWith('for (;;);{"payload":[],"domops":[["setContent","#a"', $response->getContent());
    }

    public function testStreamedWhenRequested(): void
    {
        $response = $this->kernel()->handle(Request::create('/async', 'GET', [AsyncResponse::STREAM_PARAM => 1]));

        $this->assertInstanceOf(StreamedResponse::class, $response);

        $output = '';
        ob_start(static function (string $chunk) use (&$output): string {
            $output .= $chunk;

            return '';
        });
        $response->sendContent();
        ob_end_clean();

        $this->assertSame(2, substr_count($output, AsyncResponse::STREAM_DELIMITER));
    }

    public function testSendTakesTheStatus(): void
    {
        $this->kernel();

        $this->assertSame(422, (new AsyncResponse())->send(422)->getStatusCode());
    }

    public function testCsrfTokenIsOffByDefault(): void
    {
        $this->assertStringNotContainsString('CSRFToken', $this->kernel()->handle(Request::create('/page'))->getContent());
    }

    public function testCsrfTokenOfTheConfiguredId(): void
    {
        $content = $this->kernel(['csrf' => ['token_id' => 'bigpipe']])->handle(Request::create('/page'))->getContent();

        $this->assertMatchesRegularExpression(
            '~\["CSRFToken",\{"token":"[^"]+","header":"X-CSRF-TOKEN","param":null,"refresh":"\\\\/bigpipe\\\\/csrf-token"\}\]~',
            $content
        );
    }

    public function testRefreshUrlSendsANewToken(): void
    {
        $response = $this->kernel(['csrf' => ['token_id' => 'bigpipe']])->handle(Request::create('/bigpipe/csrf-token'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertMatchesRegularExpression('~"define":\[\["CSRFToken",\{"token":"[^"]+"~', $response->getContent());
    }

    public function testRefreshUrlCanBeTurnedOff(): void
    {
        $kernel = $this->kernel(['csrf' => ['token_id' => 'bigpipe', 'refresh_uri' => null]]);

        $this->assertSame(404, $kernel->handle(Request::create('/bigpipe/csrf-token'))->getStatusCode());
        $this->assertStringNotContainsString('"refresh"', $kernel->handle(Request::create('/page'))->getContent());
    }

    public function testExpiredTokenOfAnAjaxRequestIsRetried(): void
    {
        $request = Request::create('/save', 'POST');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = $this->kernel(['csrf' => ['token_id' => 'bigpipe']])->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('"csrf_refresh":1', $response->getContent());
        $this->assertMatchesRegularExpression('~"define":\[\["CSRFToken",\{"token":"[^"]+"~', $response->getContent());
    }

    public function testExpiredTokenOfAPageIsNotHandled(): void
    {
        $response = $this->kernel(['csrf' => ['token_id' => 'bigpipe']])->handle(Request::create('/save', 'POST'));

        $this->assertSame(419, $response->getStatusCode());
    }

    public function testRetryCanBeTurnedOff(): void
    {
        $request = Request::create('/save', 'POST');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = $this->kernel(['csrf' => ['token_id' => 'bigpipe', 'retry' => false]])->handle($request);

        $this->assertSame(419, $response->getStatusCode());
    }
}
