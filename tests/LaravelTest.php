<?php

declare(strict_types=1);

use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Context;
use dobron\BigPipe\Laravel\AsyncResponse;
use dobron\BigPipe\Laravel\BigPipeServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @runTestsInSeparateProcesses
 */
class LaravelTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $basePath = sys_get_temp_dir() . '/bigpipe-laravel-test';
        @mkdir($basePath . '/bootstrap/cache', 0777, true);
        @mkdir($basePath . '/storage/framework/views', 0777, true);

        $this->app = Application::configure($basePath)
            ->withProviders([BigPipeServiceProvider::class])
            ->withMiddleware()
            ->withExceptions()
            ->create();

        $this->app->afterBootstrapping(LoadConfiguration::class, static function (Application $app): void {
            $app['config']->set([
                'app.key' => 'base64:' . base64_encode(str_repeat('k', 32)),
                'app.debug' => false,
                'session.driver' => 'array',
                'view.compiled' => $app->storagePath('framework/views'),
                'view.paths' => [],
            ]);
        });

        $this->app->make(Kernel::class)->bootstrap();
    }

    private function handle(Request $request): Response
    {
        $kernel = $this->app->make(Kernel::class);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $response;
    }

    public function testContextIsScopedToTheRequest(): void
    {
        $context = BigPipe::context();

        $this->assertSame($this->app->make(Context::class), $context);

        $this->app->forgetScopedInstances();

        $this->assertNotSame($context, BigPipe::context());
    }

    public function testJsmodDirectives(): void
    {
        $html = Blade::render(<<<'BLADE'
@jsmod('Feed', 'init', [$id])
@jsmodIf(in_array($id, [1, 2]), 'Shown')
@jsmodIf(false, 'Hidden')
@define('Config', ['id' => $id])
@bigpipe
BLADE, ['id' => 1]);

        $this->assertStringContainsString('"require":[["Feed","init",[1]],["Shown"]]', $html);
        $this->assertStringContainsString('"define":[["Config",{"id":1}]]', $html);
        $this->assertStringNotContainsString('Hidden', $html);
    }

    public function testBigpipeDirectiveUsesTheNonceOfVite(): void
    {
        \Illuminate\Support\Facades\Vite::useCspNonce('abc');

        $this->assertStringContainsString('<script nonce="abc">', Blade::render('@bigpipe'));
    }

    public function testControllerReturnsAsyncResponse(): void
    {
        Route::get('/async', fn () => (new AsyncResponse())->setContent('#a', 'b'));

        $response = $this->handle(Request::create('/async'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('for (;;);{"payload":[],"domops":[["setContent","#a"', $response->getContent());
    }

    public function testSendTakesTheStatus(): void
    {
        $this->app->instance('request', Request::create('/'));

        $this->assertSame(422, (new AsyncResponse())->send(422)->getStatusCode());
    }

    public function testStreamedWhenRequested(): void
    {
        Route::get('/async', fn () => (new AsyncResponse())->setContent('#a', 'b'));

        $response = $this->handle(Request::create('/async', 'GET', [AsyncResponse::STREAM_PARAM => 1]));

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

    public function testMiddlewareSendsTheCsrfToken(): void
    {
        Route::middleware('web')->get('/page', fn () => Blade::render('@bigpipe'));

        $content = $this->handle(Request::create('/page'))->getContent();

        $this->assertMatchesRegularExpression(
            '~\["CSRFToken",\{"token":"\w{40}","header":"X-CSRF-TOKEN","param":null,"refresh":"\\\\/bigpipe\\\\/csrf-token"\}\]~',
            $content
        );
    }

    public function testRefreshRouteSendsANewToken(): void
    {
        $content = $this->handle(Request::create('/bigpipe/csrf-token'))->getContent();

        $this->assertMatchesRegularExpression('~"define":\[\["CSRFToken",\{"token":"\w{40}"~', $content);
    }

    public function testExpiredTokenOfAnAjaxRequestIsRetried(): void
    {
        Route::middleware('web')->post('/save', fn () => 'saved');

        $request = Request::create('/save', 'POST', ['_token' => 'expired']);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = $this->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('"csrf_refresh":1', $response->getContent());
    }

    public function testExpiredTokenOfAPageIsNotHandled(): void
    {
        Route::middleware('web')->post('/save', fn () => 'saved');

        $this->assertSame(419, $this->handle(Request::create('/save', 'POST', ['_token' => 'expired']))->getStatusCode());
    }
}
