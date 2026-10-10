<?php

namespace dobron\BigPipe\Laravel;

use dobron\BigPipe\BigPipe;
use Illuminate\Container\Container;
use Illuminate\Foundation\Vite;

/**
 * @internal what the Blade directives of BigPipe compile to, see BigPipeServiceProvider
 */
final class Blade
{
    /**
     * @bigpipe: the script of the page, with the CSP nonce of Vite unless one was set.
     *
     * @throws \Throwable
     */
    public static function render(): string
    {
        $context = BigPipe::context();

        if ($context->nonce === null && class_exists(Vite::class)) {
            $context->nonce = Container::getInstance()->make(Vite::class)->cspNonce();
        }

        return BigPipe::render();
    }

    /**
     * @jsmod('Module', 'method', [$arg]): calls a JavaScript module once the page is loaded.
     *
     * @throws \Throwable
     */
    public static function jsmod(string $module, ?string $method = null, array $args = [], ?int $priority = null): void
    {
        BigPipe::page()->call($module, $method, $args, $priority);
    }

    /**
     * @jsmodIf($condition, 'Module', 'method', [$arg]): calls the module when the condition holds.
     *
     * @throws \Throwable
     */
    public static function jsmodIf(
        mixed $condition,
        string $module,
        ?string $method = null,
        array $args = [],
        ?int $priority = null
    ): void {
        if ($condition) {
            static::jsmod($module, $method, $args, $priority);
        }
    }

    /**
     * @define('Module', $exports): sends data the browser can require as a module.
     *
     * @throws \Throwable
     */
    public static function define(string $module, mixed $exports): void
    {
        BigPipe::page()->define($module, $exports);
    }
}
