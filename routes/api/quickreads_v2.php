<?php

use App\Http\Controllers\Api\V2\{AppleAuthController, AppleLifecycleController, AuthController, CatalogController, FavoritesController, StoryActivityController};
use App\Http\Middleware\Api\V2\{Authenticate, JsonRequests, SensitiveRequests};
use Illuminate\Support\Facades\Route;

Route::prefix('v2')->name('quickreads.v2.')->middleware(JsonRequests::class)->group(function () {
    Route::middleware(SensitiveRequests::class)->group(function () {
        Route::post('auth/apple/challenge', [AppleAuthController::class, 'challenge'])->middleware('throttle:10,1,quickreads-apple-challenge-')->name('apple.challenge');
        Route::post('auth/apple', [AppleAuthController::class, 'exchange'])->middleware('throttle:10,1,quickreads-apple-exchange-')->name('apple.exchange');
        Route::post('auth/apple/notifications', [AppleLifecycleController::class, 'notification'])->name('apple.notifications');
        Route::post('story-activity', [StoryActivityController::class, 'store'])->middleware('throttle:30,1,quickreads-activity-')->name('activity.store');
        Route::delete('me', [AppleLifecycleController::class, 'deleteAccount'])->middleware(Authenticate::class)->name('account.delete');
    });
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1,quickreads-v2-login-')->name('login');
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1,quickreads-v2-register-')->name('register');
    Route::post('auth/password/email', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1,quickreads-v2-forgotPassword-')->name('password.email');
    Route::post('auth/password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1,quickreads-v2-resetPassword-')->name('password.reset');

    Route::get('explore', [CatalogController::class, 'explore'])->name('explore');
    Route::get('stories', [CatalogController::class, 'stories'])->name('stories');
    Route::get('stories/resolve', [CatalogController::class, 'resolve'])->name('stories.resolve');
    Route::get('stories/{storyId}', [CatalogController::class, 'story'])->where('storyId', '[1-9][0-9]{0,9}')->name('story');
    Route::get('stories/{storyId}/content', [CatalogController::class, 'content'])->where('storyId', '[1-9][0-9]{0,9}')->name('content');
    Route::get('authors', [CatalogController::class, 'authors'])->name('authors');
    Route::get('categories', [CatalogController::class, 'categories'])->name('categories');

    Route::middleware(Authenticate::class)->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me/favorites', [FavoritesController::class, 'index'])->name('favorites');
        Route::put('me/favorites/{storyId}', [FavoritesController::class, 'put'])->where('storyId', '[1-9][0-9]{0,9}')->name('favorites.put');
        Route::delete('me/favorites/{storyId}', [FavoritesController::class, 'delete'])->where('storyId', '[1-9][0-9]{0,9}')->name('favorites.delete');
    });
});
