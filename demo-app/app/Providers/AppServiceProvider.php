<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The class of <body>. A page transition replaces it with the one of its response, so the layout
        // and the transitions have to use the same.
        View::share('bodyClass', 'theme');

        // The build is a classic script, not a module: see vite.config.js.
        Vite::useScriptTagAttributes(fn () => Vite::isRunningHot() ? [] : ['type' => false]);
        // No modulepreload for it: it is not a module, and the single file has nothing else to preload.
        Vite::usePreloadTagAttributes(fn () => Vite::isRunningHot() ? [] : false);
    }
}
