<?php

namespace dobron\BigPipe\Symfony\Twig;

use dobron\BigPipe\BigPipe;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * {{ bigpipe_jsmod('Feed', 'init', [feed.id]) }}, {{ bigpipe_define('Config', config) }} and, at the
 * end of the page, {{ bigpipe() }}.
 */
class BigPipeExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('bigpipe', [$this, 'render'], ['is_safe' => ['html']]),
            new TwigFunction('bigpipe_jsmod', [$this, 'jsmod']),
            new TwigFunction('bigpipe_define', [$this, 'define']),
        ];
    }

    /**
     * The script that sends the pagelets and the modules of the page, see BigPipe::render().
     *
     * @throws \Throwable
     */
    public function render(): string
    {
        return BigPipe::render();
    }

    /**
     * Calls a JavaScript module once the page is loaded, see BigPipe::call().
     *
     * @throws \Throwable
     */
    public function jsmod(string $module, ?string $method = null, array $args = [], ?int $priority = null): string
    {
        BigPipe::page()->call($module, $method, $args, $priority);

        return '';
    }

    /**
     * Sends data the browser can require as a module, see BigPipe::define().
     *
     * @throws \Throwable
     */
    public function define(string $module, mixed $exports): string
    {
        BigPipe::page()->define($module, $exports);

        return '';
    }
}
