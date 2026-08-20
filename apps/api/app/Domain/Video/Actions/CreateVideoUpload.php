<?php

namespace App\Domain\Video\Actions;

use App\Domain\Video\Contracts\VideoStorageGateway;
use App\Domain\Video\Data\VideoUploadInitiation;
use App\Domain\Video\Enums\VideoSourceStatus;
use App\Domain\Video\Models\Video;

class CreateVideoUpload
{
    public function __construct(private readonly VideoStorageGateway $gateway)
    {
    }

    public function __invoke(Video $video): VideoUploadInitiation
    {
        $initiation = $this->gateway->createUpload($video);

        $video->update([
            'provider_video_id' => $initiation->providerVideoId,
            'source_status' => VideoSourceStatus::Processing,
        ]);

        return $initiation;
    }
}
