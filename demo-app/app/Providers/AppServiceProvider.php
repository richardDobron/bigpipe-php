<?php

namespace App\Providers;

use dobron\BigPipe\BigPipe;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    private static $init = false;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        View::composer('*', function () {
            if (!request()->ajax() && !self::$init) {
                self::$init = true;

                $bigPipe = new BigPipe();
                $bigPipe->require(
                    "require('Primer')"
                );
            }
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
