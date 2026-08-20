<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\RegisterCreatorController;
use App\Http\Controllers\Api\Creator\PayoutController;
use App\Http\Controllers\Api\Creator\VideoController as CreatorVideoController;
use App\Http\Controllers\Api\Creator\VideoSourceController;
use App\Http\Controllers\Api\OrangeMoneyWebhookController;
use App\Http\Controllers\Api\VideoCatalogController;
use App\Http\Controllers\Api\VideoPurchaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [RegisterController::class, 'store'])->name('register');
Route::post('/register/creator', [RegisterCreatorController::class, 'store'])->name('register.creator');
Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login');

Route::match(['get', 'post'], '/webhooks/orange-money', OrangeMoneyWebhookController::class)
    ->name('webhooks.orange-money');

Route::get('/videos', [VideoCatalogController::class, 'index'])->name('videos.index');
Route::get('/videos/{video}', [VideoCatalogController::class, 'show'])->name('videos.show');

Route::middleware(['auth:sanctum', 'account.active'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [LogoutController::class, 'store'])->name('logout');

    Route::post('/videos/{video}/purchase', [VideoPurchaseController::class, 'store'])
        ->name('videos.purchase');

    Route::prefix('creator')->name('creator.')->group(function () {
        Route::get('/videos', [CreatorVideoController::class, 'index'])->name('videos.index');
        Route::post('/videos', [CreatorVideoController::class, 'store'])->name('videos.store');
        Route::post('/videos/{video}/source', [VideoSourceController::class, 'store'])->name('videos.source.store');
        Route::get('/videos/{video}/source', [VideoSourceController::class, 'show'])->name('videos.source.show');

        Route::get('/balance', [PayoutController::class, 'balance'])->name('balance');
        Route::get('/payouts', [PayoutController::class, 'index'])->name('payouts.index');
        Route::post('/payouts', [PayoutController::class, 'store'])->name('payouts.store');
    });
});
