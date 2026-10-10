<?php

namespace dobron\BigPipe\Laravel\Middleware;

use Closure;
use dobron\BigPipe\BigPipe;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;

/**
 * Sends the CSRF token of the session to the browser, see the "csrf" options of config/bigpipe.php.
 */
class SetUpBigPipe
{
    public function __construct(private Repository $config)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $csrf = $this->config->get('bigpipe.csrf', []);

        if (($csrf['enabled'] ?? true) && $request->hasSession() && $request->session()->token() !== null) {
            BigPipe::setCSRFToken(
                $request->session()->token(),
                $csrf['header'] ?? 'X-CSRF-TOKEN',
                $csrf['param'] ?? null,
                $csrf['refresh_uri'] ?? null
            );
        }

        return $next($request);
    }
}
