<?php

namespace App\Domain\Video\Actions;

use App\Domain\Video\Contracts\VideoStorageGateway;
use App\Domain\Video\Enums\VideoSourceStatus;
use App\Domain\Video\Models\Video;

class RefreshVideoSourceStatus
{
    public function __construct(private readonly VideoStorageGateway $gateway)
    {
    }

    public function __invoke(Video $video): Video
    {
        if ($video->source_status !== VideoSourceStatus::Processing) {
            return $video;
        }

        $state = $this->gateway->fetchState($video);

        $video->update([
            'source_status' => $state->status,
            'playback_url' => $state->playbackUrl,
        ]);

        return $video->fresh();
    }
}
