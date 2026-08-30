<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\RegisterCreatorController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CloudflareStreamWebhookController;
use App\Http\Controllers\Api\Creator\MessageController;
use App\Http\Controllers\Api\Creator\PayoutController;
use App\Http\Controllers\Api\Creator\StatsController;
use App\Http\Controllers\Api\Creator\UpgradeController;
use App\Http\Controllers\Api\Creator\VideoController as CreatorVideoController;
use App\Http\Controllers\Api\Creator\VideoSourceController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrangeMoneyWebhookController;
use App\Http\Controllers\Api\PayDunyaWebhookController;
use App\Http\Controllers\Api\VideoCatalogController;
use App\Http\Controllers\Api\VideoFavoriteController;
use App\Http\Controllers\Api\VideoPurchaseController;
use App\Http\Controllers\Api\VideoRecommendationController;
use App\Http\Controllers\Api\VideoReportController;
use App\Http\Controllers\Api\VideoReviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [RegisterController::class, 'store'])
    ->middleware('throttle:register')
    ->name('register');
Route::post('/register/creator', [RegisterCreatorController::class, 'store'])
    ->middleware('throttle:register')
    ->name('register.creator');
Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login');

Route::match(['get', 'post'], '/webhooks/orange-money', OrangeMoneyWebhookController::class)
    ->name('webhooks.orange-money');
Route::post('/webhooks/paydunya', PayDunyaWebhookController::class)
    ->name('webhooks.paydunya');
Route::post('/webhooks/cloudflare-stream', CloudflareStreamWebhookController::class)
    ->name('webhooks.cloudflare-stream');

Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

Route::get('/videos', [VideoCatalogController::class, 'index'])->name('videos.index');
// Must come before /videos/{video} — otherwise "recommended"/"featured" is
// captured by the {video} wildcard and fails route-model binding.
Route::get('/videos/recommended', [VideoRecommendationController::class, 'index'])->name('videos.recommended');
Route::get('/videos/featured', [VideoCatalogController::class, 'featured'])->name('videos.featured');
Route::get('/videos/{video}', [VideoCatalogController::class, 'show'])->name('videos.show');
Route::post('/videos/{video}/view', [VideoCatalogController::class, 'view'])->name('videos.view');
Route::get('/videos/{video}/reviews', [VideoReviewController::class, 'index'])->name('videos.reviews.index');

Route::middleware(['auth:sanctum', 'account.active'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [LogoutController::class, 'store'])->name('logout');

    Route::get('/account/export', [AccountController::class, 'export'])->name('account.export');
    Route::delete('/account', [AccountController::class, 'destroy'])
        ->middleware('throttle:write-action')
        ->name('account.destroy');

    Route::post('/videos/{video}/purchase', [VideoPurchaseController::class, 'store'])
        ->middleware('throttle:purchase')
        ->name('videos.purchase');
    Route::post('/videos/{video}/report', [VideoReportController::class, 'store'])
        ->middleware('throttle:write-action')
        ->name('videos.report');
    Route::post('/videos/{video}/reviews', [VideoReviewController::class, 'store'])
        ->middleware('throttle:write-action')
        ->name('videos.reviews.store');
    Route::post('/videos/{video}/favorite', [VideoFavoriteController::class, 'store'])
        ->middleware('throttle:write-action')
        ->name('videos.favorite');

    Route::get('/favorites', [VideoFavoriteController::class, 'index'])->name('favorites.index');
    Route::get('/purchases', [VideoPurchaseController::class, 'index'])->name('purchases.index');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])
        ->name('notifications.read');

    Route::prefix('creator')->name('creator.')->group(function () {
        Route::post('/upgrade', [UpgradeController::class, 'store'])
            ->middleware('throttle:write-action')
            ->name('upgrade');

        Route::get('/videos', [CreatorVideoController::class, 'index'])->name('videos.index');
        Route::post('/videos', [CreatorVideoController::class, 'store'])->name('videos.store');
        Route::post('/videos/{video}/source', [VideoSourceController::class, 'store'])->name('videos.source.store');
        Route::get('/videos/{video}/source', [VideoSourceController::class, 'show'])->name('videos.source.show');

        Route::get('/balance', [PayoutController::class, 'balance'])->name('balance');
        Route::get('/payouts', [PayoutController::class, 'index'])->name('payouts.index');
        Route::post('/payouts', [PayoutController::class, 'store'])
            ->middleware('throttle:write-action')
            ->name('payouts.store');

        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::post('/messages', [MessageController::class, 'store'])
            ->middleware('throttle:write-action')
            ->name('messages.store');

        Route::get('/stats', [StatsController::class, 'index'])->name('stats');
    });
});
