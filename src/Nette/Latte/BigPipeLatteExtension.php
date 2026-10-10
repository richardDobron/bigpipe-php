<?php

namespace dobron\BigPipe\Nette\Latte;

use dobron\BigPipe\BigPipe;
use Latte\Extension;
use Latte\Runtime\Html;

/**
 * {jsmod('Feed', 'init', [$feed->id])}, {jsdefine('Config', $config)} and, at the end of the
 * layout, {bigpipe()}.
 */
class BigPipeLatteExtension extends Extension
{
    public function getFunctions(): array
    {
        return [
            'bigpipe' => [$this, 'render'],
            'jsmod' => [$this, 'jsmod'],
            'jsdefine' => [$this, 'define'],
        ];
    }

    /**
     * The script that sends the pagelets and the modules of the page, see BigPipe::render().
     *
     * @throws \Throwable
     */
    public function render(): Html
    {
        return new Html(BigPipe::render());
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
