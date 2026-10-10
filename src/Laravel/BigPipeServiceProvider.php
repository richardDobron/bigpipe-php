<?php

namespace dobron\BigPipe\Laravel;

use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Context;
use dobron\BigPipe\Laravel\Middleware\SetUpBigPipe;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Integrates BigPipe into Laravel, discovered by Laravel: a context per request (also on Octane),
 * the CSRF token, the recovery of an expired token and the Blade directives.
 */
class BigPipeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/bigpipe.php', 'bigpipe');

        // Flushed at the start of every request, so a worker that serves many requests does not
        // carry the pagelets and modules of one request into the next.
        $this->app->scoped(Context::class, fn () => new Context());
    }

    public function boot(): void
    {
        BigPipe::setContextResolver(fn (): Context => $this->app->make(Context::class));

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/bigpipe.php' => $this->app->configPath('bigpipe.php'),
            ], 'bigpipe-config');
        }

        $this->callAfterResolving('blade.compiler', fn (BladeCompiler $blade) => $this->registerDirectives($blade));
        $this->registerMiddleware();
        $this->registerCsrfRecovery();
    }

    protected function registerDirectives(BladeCompiler $blade): void
    {
        $blade->directive('bigpipe', fn () => '<?php echo \\' . Blade::class . '::render(); ?>');

        foreach (['jsmod', 'jsmodIf', 'define'] as $directive) {
            $blade->directive(
                $directive,
                fn (string $expression) => '<?php \\' . Blade::class . "::$directive($expression); ?>"
            );
        }
    }

    protected function registerMiddleware(): void
    {
        if (!$this->app['config']->get('bigpipe.middleware')) {
            return;
        }

        $this->callAfterResolving('router', function (Router $router): void {
            $router->pushMiddlewareToGroup('web', SetUpBigPipe::class);
        });
    }

    protected function registerCsrfRecovery(): void
    {
        $csrf = $this->app['config']->get('bigpipe.csrf', []);

        if (!($csrf['enabled'] ?? true)) {
            return;
        }

        $arguments = static fn (Request $request): array => [
            $request->session()->token(),
            $csrf['header'] ?? 'X-CSRF-TOKEN',
            $csrf['param'] ?? null,
            $csrf['refresh_uri'] ?? null,
        ];

        $uri = $csrf['refresh_uri'] ?? null;

        if ($uri !== null && !$this->app->routesAreCached()) {
            $this->callAfterResolving('router', function (Router $router) use ($uri, $arguments): void {
                $router->get($uri, function (Request $request) use ($arguments) {
                    $module = BigPipe::csrfTokenModule(...$arguments($request));

                    return (new AsyncResponse())->define(BigPipe::CSRF_TOKEN_MODULE, $module);
                })
                    ->middleware('web')
                    ->withoutMiddleware(SetUpBigPipe::class)
                    ->name('bigpipe.csrf-token');
            });
        }

        if ($csrf['retry'] ?? true) {
            $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler) use ($arguments): void {
                if (!method_exists($handler, 'renderable')) {
                    return;
                }

                $handler->renderable(function (HttpException $e, Request $request) use ($arguments) {
                    if ($e->getStatusCode() !== 419 || !$request->ajax() || !$request->hasSession()) {
                        return null;
                    }

                    // Never streamed: the browser reads the new token from the whole response.
                    $response = (new AsyncResponse())->retryWithCSRFToken(...$arguments($request));

                    return new Response($response->buildResponseString(), 200, AsyncResponse::headers());
                });
            });
        }
    }
}
