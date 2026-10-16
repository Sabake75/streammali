<?php

namespace App\Providers;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Gateways\OrangeMoneyGateway;
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
        $this->app->bind(PaymentGateway::class, OrangeMoneyGateway::class);
        $this->app->bind(VideoStorageGateway::class, CloudflareStreamGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // A 4-digit code has only 10 000 combinations, so login attempts must
        // be throttled per phone+IP to keep brute-forcing impractical. That
        // alone isn't enough against an attacker rotating through many IPs
        // (cheap with proxies) while keeping the same target phone — 5/IP/
        // minute lets them exhaust all 10 000 combinations in well under an
        // hour with a modest proxy pool. The second, phone-only limit closes
        // that gap: it doesn't care how many IPs the attempts come from.
        RateLimiter::for('login', function (Request $request) {
            $phone = Str::lower((string) $request->input('phone'));

            return [
                Limit::perMinute(5)->by($phone.'|'.$request->ip()),
                Limit::perHour(20)->by($phone),
            ];
        });

        // Account creation was completely unthrottled — by IP (no user yet
        // to key on) rather than phone, since the phone itself is what an
        // abuser would be varying.
        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(5)->by($request->ip());
        });

        // Every purchase call hits Orange Money's real API to create an
        // invoice — generous enough for a legitimate binge-buying session,
        // tight enough to make hammering the payment gateway impractical.
        RateLimiter::for('purchase', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Shared ceiling for the rest of the authenticated write endpoints
        // (reviews, reports, favorites, messages, payout requests, creator
        // video creation/upload) — none of these were throttled at all
        // before. Generous enough that no real user ever notices it, tight
        // enough that a compromised or scripted client can't hammer the API
        // — creator video upload in particular calls Cloudflare Stream's
        // real API on every request, reserving a TUS upload slot each time.
        RateLimiter::for('write-action', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        // Public, unauthenticated, and previously completely unthrottled —
        // fired on every real page view but trivial to script-spam to
        // inflate a video's view count (skews "populaire" sort and the
        // count itself, both shown as real numbers to viewers/creators).
        // Per IP since there's no user to key on; generous enough for
        // someone binge-browsing the catalogue in one sitting.
        RateLimiter::for('view-tracking', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
