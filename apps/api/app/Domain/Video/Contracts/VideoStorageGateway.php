<?php

namespace App\Domain\Video\Contracts;

use App\Domain\Video\Data\VideoPreviewState;
use App\Domain\Video\Data\VideoSourceState;
use App\Domain\Video\Data\VideoUploadInitiation;
use App\Domain\Video\Models\Video;

interface VideoStorageGateway
{
    /**
     * Ask the provider for a one-time direct-upload URL. The video bytes
     * are sent straight from the creator's client to the provider — our
     * backend never proxies the file itself.
     */
    public function createUpload(Video $video): VideoUploadInitiation;

    /**
     * Ask the provider for the current transcoding/playback state.
     *
     * Polled on demand rather than trusting an inbound webhook, same
     * reasoning as Domain\Payment\Contracts\PaymentGateway::verifyStatus.
     */
    public function fetchState(Video $video): VideoSourceState;

    /**
     * Ask the provider to derive a short, freely-playable clip from the
     * full (gated) source — the "aperçu gratuit avant achat" clip, given
     * its own provider id/playback URL distinct from the full video's.
     */
    public function createClip(Video $video): VideoPreviewState;
}
