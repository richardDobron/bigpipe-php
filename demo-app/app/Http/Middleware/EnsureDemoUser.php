<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * The demo has no login: everybody is the same demo user.
 */
class EnsureDemoUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            Auth::login(User::firstOrCreate(
                ['email' => 'demo@example.com'],
                ['name' => 'Demo User', 'password' => Hash::make(Str::random(40))]
            ));
        }

        return $next($request);
    }
}
