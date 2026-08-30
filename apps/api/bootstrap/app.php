<?php

use App\Http\Middleware\EnsureAccountIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'account.active' => EnsureAccountIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // No-op until SENTRY_LARAVEL_DSN is set (see .env.example) — no
        // account exists yet for this project, so this stays fully inert
        // in the meantime rather than half-wired.
        Integration::handles($exceptions);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Third-party gateway calls (Orange Money, Cloudflare Stream) can fail
        // for reasons unrelated to the request itself (outage, timeout, bad
        // credentials) — never leak the raw upstream response/stack trace.
        $exceptions->render(function (RequestException|ConnectionException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Un service externe est momentanément indisponible. Réessaie dans quelques instants.',
                ], 502);
            }
        });

        // Message hardcoded in English inside Laravel's own
        // ValidatePostSize middleware — not a lang/ key, so it can't be
        // translated via lang/fr/validation.php like everything else.
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Le fichier envoyé est trop volumineux.',
                ], 413);
            }
        });
    })->create();
