<?php

namespace App\Domain\Video\Gateways;

use App\Domain\Video\Contracts\VideoStorageGateway;
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

        return new VideoSourceState(
            status: match ($state) {
                'ready' => VideoSourceStatus::Ready,
                'error' => VideoSourceStatus::Failed,
                default => VideoSourceStatus::Processing,
            },
            playbackUrl: $result['playback']['hls'] ?? null,
        );
    }

    private function apiBaseUrl(): string
    {
        $accountId = config('services.cloudflare_stream.account_id');

        return "https://api.cloudflare.com/client/v4/accounts/{$accountId}";
    }
}
