<?php

namespace App\Domain\Video\Contracts;

use App\Domain\Video\Data\VideoDownloadState;
use App\Domain\Video\Data\VideoPreviewState;
use App\Domain\Video\Data\VideoSourceState;
use App\Domain\Video\Data\VideoUploadInitiation;
use App\Domain\Video\Models\Video;

interface VideoStorageGateway
{
    /**
     * Ask the provider for a one-time resumable (TUS) upload URL sized for
     * $fileSizeBytes. The video bytes are sent straight from the creator's
     * client to the provider in chunks — our backend never proxies the file
     * itself, and never sees its bytes.
     */
    public function createUpload(Video $video, int $fileSizeBytes): VideoUploadInitiation;

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

    /**
     * Ask the provider for a downloadable (MP4) rendition of the full
     * source — offline playback in the mobile app, not exposed on web (see
     * App\Http\Controllers\Api\VideoDownloadController). Generation is
     * asynchronous on the provider's side; not ready yet is a normal,
     * expected result, not an error.
     */
    public function getDownloadState(Video $video): VideoDownloadState;

    /**
     * A short-lived, tokenized version of $video->playback_url — the
     * un-tokenized URL plays for anyone who gets hold of it, with no
     * server involvement on our side once Cloudflare has it (see
     * App\Http\Resources\VideoResource, the only caller: it already
     * decides WHO gets handed a playback URL at all, this decides how
     * long it keeps working once they have it). Null when $video has no
     * playback_url/provider_video_id yet, or when no signing key is
     * configured — the caller falls back to the un-tokenized URL in that
     * case rather than breaking playback.
     */
    public function signPlaybackUrl(Video $video): ?string;
}
