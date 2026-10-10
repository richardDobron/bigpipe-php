<?php

namespace dobron\BigPipe\CakePHP;

use Cake\Core\BasePlugin;
use Cake\Http\MiddlewareQueue;
use dobron\BigPipe\Psr\BigPipeMiddleware;

/**
 * Integrates BigPipe into CakePHP: `$this->addPlugin(BigPipePlugin::class)` in Application::bootstrap().
 * Its middleware handles every request with a fresh context and sends the CSRF token of
 * CsrfProtectionMiddleware to the browser.
 */
class BigPipePlugin extends BasePlugin
{
    protected ?string $name = 'BigPipe';

    protected bool $bootstrapEnabled = false;

    protected bool $consoleEnabled = false;

    protected bool $routesEnabled = false;

    protected bool $servicesEnabled = false;

    protected bool $eventsEnabled = false;

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        // Added after the middleware of the application, so after CsrfProtectionMiddleware.
        return $middlewareQueue->add(new BigPipeMiddleware(
            static fn ($request) => $request->getAttribute('csrfToken'),
            'X-CSRF-Token'
        ));
    }
}
