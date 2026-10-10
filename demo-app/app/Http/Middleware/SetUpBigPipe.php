<?php

namespace App\Http\Middleware;

use Closure;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Quickling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SetUpBigPipe
{
    public function handle(Request $request, Closure $next): Response
    {
        // Expired tokens: the browser gets a new one from this URL and sends the request again.
        BigPipe::setCSRFToken(csrf_token(), refreshUri: route('csrf-token', absolute: false));

        // The build is a classic script in the <head>, so the inline scripts of BigPipe are classic too and
        // every pagelet is shown as soon as it arrives. The dev server serves the entrypoint as a deferred
        // module: then they are modules as well, which run after it, once the page is parsed.
        BigPipe::setScriptType(Vite::isRunningHot() ? 'module' : null);
        BigPipe::setTtiPhase(0);
        BigPipe::setPipelining(! $this->isCrawler($request));

        // Every page of the site renders into the same layout, so every page is a page transition.
        Quickling::configure(
            version: config('app.version'),
            badRequestKeys: ['print']
        );

        return $next($request);
    }

    private function isCrawler(Request $request): bool
    {
        return (bool) preg_match('/bot|crawl|spider|slurp|preview/i', (string) $request->userAgent());
    }
}
