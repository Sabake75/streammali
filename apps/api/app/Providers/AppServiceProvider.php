<?php

namespace App\Providers;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Gateways\PayDunyaGateway;
use App\Domain\Video\Contracts\VideoStorageGateway;
use App\Domain\Video\Gateways\CloudflareStreamGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, PayDunyaGateway::class);
        $this->app->bind(VideoStorageGateway::class, CloudflareStreamGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // A 4-digit code has only 10 000 combinations, so login attempts must
        // be throttled per phone+IP to keep brute-forcing impractical.
        RateLimiter::for('login', function (Request $request) {
            $key = Str::lower((string) $request->input('phone')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });

        // Account creation was completely unthrottled — by IP (no user yet
        // to key on) rather than phone, since the phone itself is what an
        // abuser would be varying.
        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(5)->by($request->ip());
        });

        // Every purchase call hits PayDunya's real API to create an invoice
        // — generous enough for a legitimate binge-buying session, tight
        // enough to make hammering the payment gateway impractical.
        RateLimiter::for('purchase', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Shared ceiling for the rest of the authenticated write endpoints
        // (reviews, reports, favorites, messages, payout requests) — none of
        // these were throttled at all before. Generous enough that no real
        // user ever notices it, tight enough that a compromised or scripted
        // client can't hammer the API.
        RateLimiter::for('write-action', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}
