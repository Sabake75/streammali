<?php

namespace App\Http\Controllers\Api;

use App\Domain\Video\Actions\CreatePreviewClip;
use App\Domain\Video\Actions\RefreshVideoSourceStatus;
use App\Domain\Video\Enums\VideoSourceStatus;
use App\Domain\Video\Models\Video;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CloudflareStreamWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        RefreshVideoSourceStatus $refreshVideoSourceStatus,
        CreatePreviewClip $createPreviewClip,
    ): JsonResponse {
        $video = Video::where('provider_video_id', $request->input('uid'))->firstOrFail();

        // The notification payload itself isn't trusted — same reasoning as
        // OrangeMoneyWebhookController: RefreshVideoSourceStatus re-fetches
        // the real state directly from Cloudflare before changing anything.
        $video = $refreshVideoSourceStatus($video);

        // Only from the webhook path, not the on-demand refresh action —
        // otherwise every manual status poll would attempt to (re-)create a
        // clip. CreatePreviewClip is itself idempotent as a second guard.
        if ($video->source_status === VideoSourceStatus::Ready) {
            $createPreviewClip($video);
        }

        return response()->json(['status' => 'ok']);
    }
}
