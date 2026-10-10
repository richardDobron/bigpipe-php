<?php

use App\Arch\BigPipe\AsyncResponse;
use App\Http\Middleware\RequireCsrfToken;
use App\Http\Middleware\SetUpBigPipe;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The demo checks the CSRF token of every request, see RequireCsrfToken.
        $middleware->web(
            append: [SetUpBigPipe::class],
            replace: [PreventRequestForgery::class => RequireCsrfToken::class],
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // The validation errors of a request made by BigPipe: the messages next to the fields.
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->ajax()) {
                return null;
            }

            $response = new AsyncResponse();
            $selector = fn (string $field) => '#error-'.str_replace('.', '-', $field);

            foreach (array_keys($request->except('_token')) as $field) {
                $response->setContent($selector($field), '');
            }
            foreach ($e->errors() as $field => $messages) {
                $response->setContent($selector($field), e($messages[0]));
            }

            // Status 200 on purpose: an HTTP error would skip the DOM operations above.
            return $response
                ->setError('Please check the form', 'Some fields need your attention.', code: 422)
                ->send();
        });

        // An expired CSRF token (Laravel turns it into a 419): the browser sends the request again.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419 && $request->ajax()) {
                return (new AsyncResponse())->retryWithCSRFToken(csrf_token())->send();
            }

            return null;
        });
    })->create();
