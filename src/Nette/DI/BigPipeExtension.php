<?php

namespace dobron\BigPipe\Nette\DI;

use dobron\BigPipe\Nette\Latte\BigPipeLatteExtension;
use Nette\Bridges\ApplicationDI\LatteExtension;
use Nette\DI\CompilerExtension;

/**
 * Registers the Latte functions of BigPipe:
 *
 *     extensions:
 *         bigpipe: dobron\BigPipe\Nette\DI\BigPipeExtension
 */
class BigPipeExtension extends CompilerExtension
{
    public function beforeCompile(): void
    {
        foreach ($this->compiler->getExtensions(LatteExtension::class) as $latte) {
            // Latte 3 only, Latte 2 has no extensions.
            if (method_exists($latte, 'addExtension')) {
                $latte->addExtension(BigPipeLatteExtension::class);
            }
        }
    }
}
