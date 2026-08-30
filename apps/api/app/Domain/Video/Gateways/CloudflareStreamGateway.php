<?php

namespace App\Domain\Video\Gateways;

use App\Domain\Video\Contracts\VideoStorageGateway;
use App\Domain\Video\Data\VideoPreviewState;
use App\Domain\Video\Data\VideoSourceState;
use App\Domain\Video\Data\VideoUploadInitiation;
use App\Domain\Video\Enums\VideoSourceStatus;
use App\Domain\Video\Models\Video;
use Illuminate\Support\Facades\Http;

/**
 * Cloudflare Stream integration, "direct creator upload" flow.
 *
 * Implémentation basée sur le contrat documenté publiquement pour l'API
 * Cloudflare Stream (endpoint `direct_upload` pour obtenir une URL
 * d'upload à usage unique, puis lecture du statut via l'endpoint de
 * détail vidéo). Comme pour Orange Money, je n'ai pas de compte
 * Cloudflare réel pour vérifier ce contrat en conditions réelles — à
 * confirmer contre la doc Cloudflare (developers.cloudflare.com/stream)
 * une fois un compte disponible. Voir config/services.php.
 */
class CloudflareStreamGateway implements VideoStorageGateway
{
    public function createUpload(Video $video): VideoUploadInitiation
    {
        $config = config('services.cloudflare_stream');

        $response = Http::withToken($config['api_token'])
            ->baseUrl($this->apiBaseUrl())
            ->post('/stream/direct_upload', [
                'maxDurationSeconds' => $config['max_duration_seconds'],
                'requireSignedURLs' => false,
            ])
            ->throw()
            ->json();

        return new VideoUploadInitiation(
            uploadUrl: $response['result']['uploadURL'],
            providerVideoId: $response['result']['uid'],
        );
    }

    public function fetchState(Video $video): VideoSourceState
    {
        $config = config('services.cloudflare_stream');

        $response = Http::withToken($config['api_token'])
            ->baseUrl($this->apiBaseUrl())
            ->get("/stream/{$video->provider_video_id}")
            ->throw()
            ->json();

        $result = $response['result'];
        $state = $result['status']['state'] ?? null;

        // Cloudflare reports -1 (and possibly 0) while the duration isn't
        // known yet (upload still processing) — only a positive value is a
        // real, usable duration.
        $duration = $result['duration'] ?? null;

        return new VideoSourceState(
            status: match ($state) {
                'ready' => VideoSourceStatus::Ready,
                'error' => VideoSourceStatus::Failed,
                default => VideoSourceStatus::Processing,
            },
            playbackUrl: $result['playback']['hls'] ?? null,
            durationSeconds: is_numeric($duration) && $duration > 0 ? (int) round($duration) : null,
            // Cloudflare auto-generates this for every video (a frame
            // grabbed from partway through, no separate upload needed) —
            // same response as the rest of this method, no extra call.
            posterUrl: $result['thumbnail'] ?? null,
        );
    }

    /**
     * Cloudflare Stream's Clip API (documented publicly, unverified against
     * a real account like the rest of this gateway — see class docblock).
     * Derives a short standalone clip from the source video's first N
     * seconds; it gets its own `uid`/HLS manifest, so it can be served to
     * everyone (even guests) without exposing the full, gated asset.
     */
    public function createClip(Video $video): VideoPreviewState
    {
        $config = config('services.cloudflare_stream');
        $endSeconds = min($config['preview_duration_seconds'], $video->duration_seconds ?? $config['preview_duration_seconds']);

        $response = Http::withToken($config['api_token'])
            ->baseUrl($this->apiBaseUrl())
            ->post('/stream/clip', [
                'clippedFromVideoUID' => $video->provider_video_id,
                'startTimeSeconds' => 0,
                'endTimeSeconds' => $endSeconds,
            ])
            ->throw()
            ->json();

        $result = $response['result'];

        return new VideoPreviewState(
            providerVideoId: $result['uid'],
            playbackUrl: $result['playback']['hls'] ?? null,
        );
    }

    private function apiBaseUrl(): string
    {
        $accountId = config('services.cloudflare_stream.account_id');

        return "https://api.cloudflare.com/client/v4/accounts/{$accountId}";
    }
}
