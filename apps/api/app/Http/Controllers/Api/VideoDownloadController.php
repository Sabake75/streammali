<?php

namespace App\Http\Controllers\Api;

use App\Domain\Video\Contracts\VideoStorageGateway;
use App\Domain\Video\Enums\VideoSourceStatus;
use App\Domain\Video\Models\Video;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile-only offline download (see CLAUDE.md's roadmap) — deliberately no
 * web equivalent: a browser-downloaded file lands in the user's plain
 * Downloads folder, trivially copyable/shareable, defeating the anti-piracy
 * constraint from the cahier des charges. The mobile app instead stores it
 * in its own sandboxed app storage, never exposed to other apps.
 */
class VideoDownloadController extends Controller
{
    public function store(Request $request, Video $video, VideoStorageGateway $gateway): JsonResponse
    {
        abort_unless($video->isPurchasedBy($request->user()), 403);
        abort_unless($video->source_status === VideoSourceStatus::Ready, 409);

        $state = $gateway->getDownloadState($video);

        return response()->json([
            'status' => $state->ready ? 'ready' : 'processing',
            'url' => $state->url,
        ]);
    }
}
