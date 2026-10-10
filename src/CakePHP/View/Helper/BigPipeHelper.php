<?php

namespace dobron\BigPipe\CakePHP\View\Helper;

use Cake\View\Helper;
use dobron\BigPipe\BigPipe;

/**
 * Load it in AppView::initialize(): `$this->addHelper('BigPipe', ['className' => BigPipeHelper::class])`.
 * Then `$this->BigPipe->jsmod('Feed', 'init', [$id])` and, at the end of the layout,
 * `<?= $this->BigPipe->render() ?>`.
 */
class BigPipeHelper extends Helper
{
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
    public function jsmod(string $module, ?string $method = null, array $args = [], ?int $priority = null): void
    {
        BigPipe::page()->call($module, $method, $args, $priority);
    }

    /**
     * Sends data the browser can require as a module, see BigPipe::define().
     *
     * @throws \Throwable
     */
    public function define(string $module, mixed $exports): void
    {
        BigPipe::page()->define($module, $exports);
    }
}
