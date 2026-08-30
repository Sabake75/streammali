<?php

use App\Http\Middleware\EnsureAccountIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use League\Flysystem\UnableToWriteFile;
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

        // Render (like Heroku/most PaaS) terminates TLS at its own edge
        // and forwards plain HTTP to the container — without this,
        // Laravel has no way to know the original request was HTTPS.
        // '*' is safe here specifically because the container isn't
        // directly reachable from the internet, only through Render's
        // proxy. Symptom without it: asset()/url() (Filament's CSS/JS on
        // /moderation) generate as http://, which the browser then
        // blocks as mixed content on the https:// page — confirmed in
        // production (a correctly-set APP_URL alone wasn't the whole
        // story; this closes the same gap at the framework level).
        $middleware->trustProxies(at: '*');
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

        // Same idea as the gateway errors above, for file storage
        // (identity documents on R2). Requires 'throw' => true on the
        // filesystem disks (config/filesystems.php) — off by default in
        // Laravel, which silently returns false instead of throwing on a
        // failed write. Confirmed in a real container with deliberately
        // wrong R2 credentials: without 'throw' => true, this never fires
        // at all — the request returns 200 with identity_document_path
        // saved as "0" (the string cast of false).
        $exceptions->render(function (UnableToWriteFile $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Le stockage est momentanément indisponible. Réessaie dans quelques instants.',
                ], 502);
            }
        });
    })->create();
