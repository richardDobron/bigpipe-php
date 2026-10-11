<?php

namespace dobron\BigPipe\CakePHP;

use Cake\Core\BasePlugin;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;
use dobron\BigPipe\CakePHP\Middleware\CsrfRecoveryMiddleware;
use dobron\BigPipe\Psr\BigPipeMiddleware;

/**
 * Integrates BigPipe into CakePHP: `$this->addPlugin(BigPipePlugin::class)` in Application::bootstrap().
 * Its middleware handles every request with a fresh context and sends the CSRF token of
 * CsrfProtectionMiddleware to the browser, and recovers an expired token, see CsrfRecoveryMiddleware.
 *
 * Options: `['csrf' => ['refresh_uri' => '/bigpipe/csrf-token', 'retry' => true]]`, null and false
 * turn them off.
 */
class BigPipePlugin extends BasePlugin
{
    protected ?string $name = 'BigPipe';

    protected bool $bootstrapEnabled = false;

    protected bool $consoleEnabled = false;

    protected bool $routesEnabled = false;

    protected bool $servicesEnabled = false;

    protected bool $eventsEnabled = false;

    /** @var array{refresh_uri: ?string, retry: bool} */
    protected array $csrf;

    public function __construct(array $options = [])
    {
        $this->csrf = ($options['csrf'] ?? []) + ['refresh_uri' => '/bigpipe/csrf-token', 'retry' => true];

        parent::__construct($options);
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $csrf = null;
        $index = null;

        foreach ($middlewareQueue as $i => $middleware) {
            if ($middleware instanceof CsrfProtectionMiddleware) {
                $csrf = $middleware;
                $index ??= $i;
                break;
            }

            if ($middleware instanceof RoutingMiddleware) {
                $index ??= $i;
            }
        }

        $refreshUri = $csrf === null ? null : $this->csrf['refresh_uri'];

        // Added after the middleware of the application, so after CsrfProtectionMiddleware.
        $middlewareQueue->add(new BigPipeMiddleware(
            static fn ($request) => $request->getAttribute('csrfToken'),
            'X-CSRF-Token',
            csrfRefreshUri: $refreshUri === null
                ? null
                : static fn ($request) => $request->getAttribute('base', '') . $refreshUri
        ));

        // Before CsrfProtectionMiddleware, to catch its exception, and before RoutingMiddleware, so
        // the refresh URL needs no route.
        if ($csrf !== null && ($refreshUri !== null || $this->csrf['retry'])) {
            $recovery = new CsrfRecoveryMiddleware($csrf, $refreshUri, (bool) $this->csrf['retry']);
            $middlewareQueue->insertAt($index, $recovery);
        }

        return $middlewareQueue;
    }
}
