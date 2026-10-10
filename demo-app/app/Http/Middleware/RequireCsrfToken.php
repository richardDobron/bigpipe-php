<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

/**
 * Laravel 13 lets a same-origin request of a modern browser through on its origin alone, whatever its token
 * is. The demo checks the token too, so that an expired one can be shown to be recovered: see the CSRF
 * tutorial and the exception handler in bootstrap/app.php.
 */
class RequireCsrfToken extends PreventRequestForgery
{
    protected function hasValidOrigin($request)
    {
        return false;
    }
}
