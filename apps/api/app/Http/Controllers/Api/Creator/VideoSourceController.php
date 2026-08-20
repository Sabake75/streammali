<?php

namespace App\Http\Controllers\Api\Creator;

use App\Domain\Video\Actions\CreateVideoUpload;
use App\Domain\Video\Actions\RefreshVideoSourceStatus;
use App\Domain\Video\Enums\VideoSourceStatus;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoSourceController extends Controller
{
    public function store(Request $request, Video $video, CreateVideoUpload $createVideoUpload): JsonResponse
    {
        $this->authorizeOwner($request, $video);

        abort_if(
            $video->source_status === VideoSourceStatus::Processing || $video->source_status === VideoSourceStatus::Ready,
            409,
            'Un envoi est déjà en cours ou terminé pour cette vidéo.',
        );

        $initiation = $createVideoUpload($video);

        return response()->json([
            'upload_url' => $initiation->uploadUrl,
            'source_status' => VideoSourceStatus::Processing->value,
        ], 201);
    }

    public function show(Request $request, Video $video, RefreshVideoSourceStatus $refreshVideoSourceStatus): JsonResponse
    {
        $this->authorizeOwner($request, $video);

        $video = $refreshVideoSourceStatus($video);

        return response()->json([
            'source_status' => [
                'value' => $video->source_status->value,
                'label' => $video->source_status->label(),
            ],
            'playback_url' => $video->playback_url,
        ]);
    }

    private function authorizeOwner(Request $request, Video $video): void
    {
        abort_unless($request->user()->role === UserRole::Creator, 403);
        abort_unless($video->creator_id === $request->user()->id, 403, "Cette vidéo n'est pas la vôtre.");
    }
}
