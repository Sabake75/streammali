<?php

use App\Http\Controllers\Api\OrangeMoneyWebhookController;
use App\Http\Controllers\Api\VideoCatalogController;
use App\Http\Controllers\Api\VideoPurchaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::match(['get', 'post'], '/webhooks/orange-money', OrangeMoneyWebhookController::class)
    ->name('webhooks.orange-money');

Route::get('/videos', [VideoCatalogController::class, 'index'])->name('videos.index');
Route::get('/videos/{video}', [VideoCatalogController::class, 'show'])->name('videos.show');
Route::post('/videos/{video}/purchase', [VideoPurchaseController::class, 'store'])
    ->middleware('auth:sanctum')
    ->name('videos.purchase');
