<?php

namespace App\Domain\Video\Actions;

use App\Domain\Video\Contracts\VideoStorageGateway;
use App\Domain\Video\Enums\VideoSourceStatus;
use App\Domain\Video\Models\Video;

class RefreshVideoSourceStatus
{
    public function __construct(private readonly VideoStorageGateway $gateway) {}

    public function __invoke(Video $video): Video
    {
        if ($video->source_status !== VideoSourceStatus::Processing) {
            return $video;
        }

        $state = $this->gateway->fetchState($video);

        $video->update([
            'source_status' => $state->status,
            'playback_url' => $state->playbackUrl,
            // Cloudflare only reports a real duration once processing is
            // done — don't clobber an already-known duration with null on a
            // refresh that didn't get one back.
            'duration_seconds' => $state->durationSeconds ?? $video->duration_seconds,
            // Cloudflare's auto-generated thumbnail is only a default —
            // never overwrite a poster already set (nothing sets one today,
            // but this keeps room for a creator/moderator override later
            // without this refresh silently reverting it).
            'poster_path' => $video->poster_path ?: $state->posterUrl,
        ]);

        return $video->fresh();
    }
}
