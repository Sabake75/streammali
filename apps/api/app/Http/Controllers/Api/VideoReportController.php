<?php

namespace App\Http\Controllers\Api;

use App\Domain\Moderation\Actions\ReportVideo;
use App\Domain\Video\Models\Video;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoReportController extends Controller
{
    public function store(Request $request, Video $video, ReportVideo $reportVideo): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $report = $reportVideo($video, $request->user(), $validated['reason']);

        return response()->json([
            'id' => $report->id,
            'message' => 'Signalement envoyé, merci — la modération va l\'examiner.',
        ], 201);
    }
}
