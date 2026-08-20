<?php

use App\Http\Controllers\Api\OrangeMoneyWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::match(['get', 'post'], '/webhooks/orange-money', OrangeMoneyWebhookController::class)
    ->name('webhooks.orange-money');
