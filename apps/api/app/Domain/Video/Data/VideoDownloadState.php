<?php

namespace App\Domain\Video\Data;

final readonly class VideoDownloadState
{
    public function __construct(
        public bool $ready,
        public ?string $url,
    ) {
    }
}
