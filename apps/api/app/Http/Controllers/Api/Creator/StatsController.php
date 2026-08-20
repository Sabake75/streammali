<?php

namespace App\Http\Controllers\Api\Creator;

use App\Domain\Creator\Actions\GetCreatorStats;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function index(Request $request, GetCreatorStats $getCreatorStats): JsonResponse
    {
        abort_unless(
            $request->user()->role === UserRole::Creator,
            403,
            'Seuls les créateurs peuvent consulter des statistiques.',
        );

        return response()->json($getCreatorStats($request->user()));
    }
}
