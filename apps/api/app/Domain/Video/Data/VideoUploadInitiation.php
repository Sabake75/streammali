<?php

namespace App\Domain\Video\Data;

final readonly class VideoUploadInitiation
{
    public function __construct(
        public string $uploadUrl,
        public string $providerVideoId,
    ) {
    }
}
