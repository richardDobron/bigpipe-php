<?php

use App\Arch\BigPipe\AsyncResponse;
use App\Http\Controllers\BasicExampleController;
use App\Http\Controllers\BootloaderController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\CsrfController;
use App\Http\Controllers\DomReferencesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DialogController;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageletController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\ViewController;
use App\Http\Middleware\EnsureDemoUser;
use dobron\BigPipe\BigPipe;
use Illuminate\Support\Facades\Route;

Route::get('/', ViewController::class)->defaults('view', 'welcome');

// An expired CSRF token: the browser gets a new one from here and sends the request again.
Route::get('/csrf-token', function () {
    BigPipe::setCSRFToken(csrf_token(), refreshUri: route('csrf-token', absolute: false));

    return (new AsyncResponse())->send();
})->name('csrf-token');

// The demo app: the Laravel recipes as a working application.
Route::prefix('app')->middleware(EnsureDemoUser::class)->group(function () {
    Route::redirect('/', '/app/posts');

    Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::post('/posts/{post}/delete-dialog', [PostController::class, 'deleteDialog'])->name('posts.delete-dialog');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('comments.store');

    Route::get('/shop', [ProductController::class, 'index'])->name('products.index');
    Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
    Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/remove/{line}', [CartController::class, 'remove'])->name('cart.remove');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/report', [DashboardController::class, 'report'])->name('dashboard.report');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/poll', [NotificationController::class, 'poll'])->name('notifications.poll');
    Route::post('/notifications/simulate', [NotificationController::class, 'simulate'])->name('notifications.simulate');
    Route::post('/notifications/read', [NotificationController::class, 'read'])->name('notifications.read');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('avatar.update');
});

Route::group(['prefix' => 'tutorial'], function () {
    Route::group(['prefix' => 'redirecting', 'as' => 'redirecting.'], function () {
        Route::get('/', ViewController::class)->defaults('view', 'tutorial.redirecting');

        Route::post('/reload', [RedirectController::class, 'reload'])->name('reload');
        Route::post('/reload-delay', [RedirectController::class, 'reloadDelay'])->name('reload-delay');
        Route::post('/redirect', [RedirectController::class, 'redirect'])->name('redirect');
        Route::post('/redirect-delay', [RedirectController::class, 'redirectDelay'])->name('redirect-delay');
    });

    Route::group(['prefix' => 'dialogs', 'as' => 'dialog.'], function () {
        Route::get('/', ViewController::class)->defaults('view', 'tutorial.dialogs');

        Route::post('/model-dialog', [DialogController::class, 'modelDialog'])->name('model-dialog');
        Route::post('/html-dialog', [DialogController::class, 'htmlDialog'])->name('html-dialog');
        Route::post('/react-dialog', [DialogController::class, 'reactDialog'])->name('react-dialog');
        Route::post('/common-dialog', [DialogController::class, 'commonDialog'])->name('common-dialog');
        Route::post('/delete-dialog', [DialogController::class, 'deleteDialog'])->name('delete-dialog');
        Route::post('/confirm-dialog', [DialogController::class, 'confirmDialog'])->name('confirm-dialog');
        Route::post('/close-dialogs', [DialogController::class, 'closeDialogs'])->name('close-dialogs');
    });

    Route::group(['prefix' => 'basic-example', 'as' => 'basic-example.'], function () {
        Route::get('/', ViewController::class)->defaults('view', 'tutorial.basic-example');

        Route::post('/stats', [BasicExampleController::class, 'statsPanel'])->name('stats');
        Route::post('/show-phone-number', [BasicExampleController::class, 'showPhoneNumber'])->name('show.phone');
        Route::post('/load-image', [BasicExampleController::class, 'loadImage'])->name('image');
    });

    Route::group(['prefix' => 'transport-markers', 'as' => 'transport-markers.'], function () {
        Route::get('/', ViewController::class)->defaults('view', 'tutorial.transport-markers');

        Route::post('/collection', [TransportController::class, 'collection'])->name('collection');
    });

    Route::group(['prefix' => 'forms', 'as' => 'forms.'], function () {
        Route::get('/', ViewController::class)->defaults('view', 'tutorial.forms');

        Route::post('/registration', [FormController::class, 'registration'])->name('registration');
    });

    Route::group(['prefix' => 'configuration', 'as' => 'configuration.'], function () {
        Route::get('/', [ConfigurationController::class, 'show']);
        Route::post('/change', [ConfigurationController::class, 'change'])->name('change');
    });

    Route::group(['prefix' => 'dom-references', 'as' => 'dom-references.'], function () {
        Route::get('/', ViewController::class)->defaults('view', 'tutorial.dom-references');

        Route::post('/highlight/{id}', [DomReferencesController::class, 'highlight'])->name('highlight');
    });

    Route::group(['prefix' => 'csrf', 'as' => 'csrf.'], function () {
        Route::get('/', ViewController::class)->defaults('view', 'tutorial.csrf');

        Route::post('/expire', [CsrfController::class, 'expire'])->name('expire');
        Route::post('/save', [CsrfController::class, 'save'])->name('save');
    });

    Route::group(['prefix' => 'payload', 'as' => 'payload.'], function () {
        Route::get('/', ViewController::class)->defaults('view', 'tutorial.payload');

        Route::post('/check-username', [UsersController::class, 'checkUsername'])->name('check.username');
    });

    Route::group(['prefix' => 'pagelets', 'as' => 'pagelets.'], function () {
        Route::get('/', [PageletController::class, 'pagelets']);
        Route::post('/refresh/{id}', [PageletController::class, 'refresh'])->name('refresh');
    });

    Route::group(['prefix' => 'lazy-pagelets', 'as' => 'lazy-pagelets.'], function () {
        Route::get('/', ViewController::class)->defaults('view', 'tutorial.lazy-pagelets');

        Route::get('/load/{id}', [PageletController::class, 'lazy'])->name('load');
    });

    Route::group(['prefix' => 'poller', 'as' => 'poller.'], function () {
        Route::get('/', ViewController::class)->defaults('view', 'tutorial.poller');

        Route::post('/deploy', [PageletController::class, 'deploy'])->name('deploy');
        Route::get('/status', [PageletController::class, 'deployStatus'])->name('status');
    });

    Route::group(['prefix' => 'bootloader', 'as' => 'bootloader.'], function () {
        Route::get('/', ViewController::class)->defaults('view', 'tutorial.bootloader');
        Route::post('/open', [BootloaderController::class, 'open'])->name('open');
    });

    Route::group(['prefix' => 'events', 'as' => 'events.'], function () {
        Route::get('/', [EventsController::class, 'show']);
        Route::post('/order', [EventsController::class, 'order'])->name('order');
        Route::post('/widget', [EventsController::class, 'widget'])->name('widget');
    });

    Route::group(['prefix' => 'morph', 'as' => 'morph.'], function () {
        Route::get('/', ViewController::class)->defaults('view', 'tutorial.morph');

        Route::post('/quote', [PageletController::class, 'quote'])->name('quote');
    });
});
