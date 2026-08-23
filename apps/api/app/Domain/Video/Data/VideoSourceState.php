<?php

namespace App\Domain\Video\Data;

use App\Domain\Video\Enums\VideoSourceStatus;

final readonly class VideoSourceState
{
    public function __construct(
        public VideoSourceStatus $status,
        public ?string $playbackUrl,
        public ?int $durationSeconds = null,
    ) {
    }
}
