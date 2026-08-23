<?php

namespace App\Domain\Video\Data;

final readonly class VideoPreviewState
{
    public function __construct(
        public string $providerVideoId,
        public ?string $playbackUrl,
    ) {}
}
