<?php

namespace App\Domain\Video\Actions;

use App\Domain\Video\Contracts\VideoStorageGateway;
use App\Domain\Video\Models\Video;

class CreatePreviewClip
{
    public function __construct(private readonly VideoStorageGateway $gateway) {}

    /**
     * Idempotent: only ever creates one preview clip per video — called
     * from the webhook path each time Cloudflare reports the source ready,
     * which would otherwise recreate a clip on every manual status refresh.
     */
    public function __invoke(Video $video): Video
    {
        if ($video->preview_provider_video_id !== null) {
            return $video;
        }

        $preview = $this->gateway->createClip($video);

        $video->update([
            'preview_provider_video_id' => $preview->providerVideoId,
            'preview_playback_url' => $preview->playbackUrl,
        ]);

        return $video->fresh();
    }
}
