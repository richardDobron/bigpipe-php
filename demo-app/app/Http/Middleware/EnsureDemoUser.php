<?php

namespace App\Http\Middleware;

use App\Tenancy\Playground;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * The demo has no login: every visitor gets a demo user with a tenant of their own, and a playground to change.
 */
class EnsureDemoUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            Auth::login(Playground::create());
        }

        return $next($request);
    }
}
