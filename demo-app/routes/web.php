<?php

use App\Http\Controllers\BasicExampleController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix' => 'tutorial'], function () {
    Route::group(['prefix' => 'redirecting', 'as' => 'redirecting.'], function () {
        Route::get('/', function () {
            return view('tutorial.redirecting');
        });

        Route::post('/reload', [RedirectController::class, 'reload'])->name('reload');
        Route::post('/reload-delay', [RedirectController::class, 'reloadDelay'])->name('reload-delay');
        Route::post('/redirect', [RedirectController::class, 'redirect'])->name('redirect');
        Route::post('/redirect-delay', [RedirectController::class, 'redirectDelay'])->name('redirect-delay');
    });

    Route::group(['prefix' => 'basic-example', 'as' => 'basic-example.'], function () {
        Route::get('/', function () {
            return view('tutorial.basic-example');
        });

        Route::post('/stats', [BasicExampleController::class, 'statsPanel'])->name('stats');
        Route::post('/show-phone-number', [BasicExampleController::class, 'showPhoneNumber'])->name('show.phone');
        Route::post('/load-image', [BasicExampleController::class, 'loadImage'])->name('image');
    });

    Route::group(['prefix' => 'transport-markers', 'as' => 'transport-markers.'], function () {
        Route::get('/', function () {
            return view('tutorial.transport-markers');
        });

        Route::post('/collection', [TransportController::class, 'collection'])->name('collection');
    });

    Route::group(['prefix' => 'forms', 'as' => 'forms.'], function () {
        Route::get('/', function () {
            return view('tutorial.forms');
        });

        Route::post('/registration', [FormController::class, 'registration'])->name('registration');
    });

    Route::group(['prefix' => 'payload', 'as' => 'payload.'], function () {
        Route::get('/', function () {
            return view('tutorial.payload');
        });

        Route::post('/check-username', [UsersController::class, 'checkUsername'])->name('check.username');
    });
});
