<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->isActive()) {
            // `account_status` on the JSON body (not just a 403 status code,
            // which plenty of ordinary authorization failures also return —
            // "Seuls les créateurs peuvent…", "Cette vidéo n'est pas la
            // vôtre." — none of which should force a client-side logout)
            // lets the web/mobile clients tell this case apart and clear the
            // stored session, the same way they already do on a 401.
            abort(response()->json([
                'message' => match ($user->account_status?->value) {
                    'suspended' => 'Votre compte est suspendu.',
                    'blocked' => 'Votre compte est bloqué.',
                    'deleted' => 'Ce compte a été supprimé.',
                    default => 'Votre compte n\'est pas actif.',
                },
                'account_status' => $user->account_status?->value,
            ], 403));
        }

        return $next($request);
    }
}
