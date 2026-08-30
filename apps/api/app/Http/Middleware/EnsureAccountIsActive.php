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
            abort(403, match ($user->account_status?->value) {
                'suspended' => 'Votre compte est suspendu.',
                'blocked' => 'Votre compte est bloqué.',
                'deleted' => 'Ce compte a été supprimé.',
                default => 'Votre compte n\'est pas actif.',
            });
        }

        return $next($request);
    }
}
