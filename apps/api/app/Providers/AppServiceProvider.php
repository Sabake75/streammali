<?php

namespace App\Providers;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Gateways\OrangeMoneyGateway;
use App\Domain\Video\Contracts\VideoStorageGateway;
use App\Domain\Video\Gateways\CloudflareStreamGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, OrangeMoneyGateway::class);
        $this->app->bind(VideoStorageGateway::class, CloudflareStreamGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
