<?php

namespace App\Http\Controllers\Api;

use App\Domain\Video\Actions\RefreshVideoSourceStatus;
use App\Domain\Video\Models\Video;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CloudflareStreamWebhookController extends Controller
{
    public function __invoke(Request $request, RefreshVideoSourceStatus $refreshVideoSourceStatus): JsonResponse
    {
        $video = Video::where('provider_video_id', $request->input('uid'))->firstOrFail();

        // The notification payload itself isn't trusted — same reasoning as
        // OrangeMoneyWebhookController: RefreshVideoSourceStatus re-fetches
        // the real state directly from Cloudflare before changing anything.
        $refreshVideoSourceStatus($video);

        return response()->json(['status' => 'ok']);
    }
}
