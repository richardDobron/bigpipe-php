<?php

namespace App\Http\Middleware;

use Closure;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Quickling;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetUpBigPipe
{
    public function handle(Request $request, Closure $next): Response
    {
        // Expired tokens: the browser gets a new one from this URL and sends the request again.
        BigPipe::setCSRFToken(csrf_token(), refreshUri: route('csrf-token', absolute: false));

        // The inline scripts of BigPipe queue their calls until the entrypoint has run, so they work with the
        // classic script of the build and with the module of the dev server alike.
        BigPipe::setTtiPhase(0);
        BigPipe::setPipelining(! $this->isCrawler($request));

        // A new deploy: the browser offers to reload, and loads the next page in full with the new scripts.
        BigPipe::setRevision(config('app.version'));

        // Every page of the site renders into the same layout, so every page is a page transition. Its
        // version is the revision of the deploy.
        Quickling::configure(badRequestKeys: ['print']);

        return $next($request);
    }

    private function isCrawler(Request $request): bool
    {
        return (bool) preg_match('/bot|crawl|spider|slurp|preview/i', (string) $request->userAgent());
    }
}
