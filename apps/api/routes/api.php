<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Creator\VideoController as CreatorVideoController;
use App\Http\Controllers\Api\OrangeMoneyWebhookController;
use App\Http\Controllers\Api\VideoCatalogController;
use App\Http\Controllers\Api\VideoPurchaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [RegisterController::class, 'store'])->name('register');
Route::post('/login', [LoginController::class, 'store'])->name('login');
Route::post('/logout', [LogoutController::class, 'store'])->middleware('auth:sanctum')->name('logout');

Route::match(['get', 'post'], '/webhooks/orange-money', OrangeMoneyWebhookController::class)
    ->name('webhooks.orange-money');

Route::get('/videos', [VideoCatalogController::class, 'index'])->name('videos.index');
Route::get('/videos/{video}', [VideoCatalogController::class, 'show'])->name('videos.show');
Route::post('/videos/{video}/purchase', [VideoPurchaseController::class, 'store'])
    ->middleware('auth:sanctum')
    ->name('videos.purchase');

Route::middleware('auth:sanctum')->prefix('creator')->name('creator.')->group(function () {
    Route::get('/videos', [CreatorVideoController::class, 'index'])->name('videos.index');
    Route::post('/videos', [CreatorVideoController::class, 'store'])->name('videos.store');
});
