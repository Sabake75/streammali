<?php

namespace App\Domain\Video\Gateways;

use App\Domain\Video\Contracts\VideoStorageGateway;
use App\Domain\Video\Data\VideoDownloadState;
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
    /**
     * The `direct_upload` JSON endpoint used before only accepts a single
     * "Basic" POST of the whole file, capped by Cloudflare at 200MB — too
     * small for most films. TUS (resumable, chunked) has no such cap and,
     * contrary to what an earlier version of this method assumed, doesn't
     * need our secret API token on every chunk: only this creation call
     * does, and it returns a one-time URL (the `Location` header) that
     * already carries its own authorization for the client's chunk PATCH
     * requests — same "never exposed to the browser" guarantee as before.
     */
    public function createUpload(Video $video, int $fileSizeBytes): VideoUploadInitiation
    {
        $config = config('services.cloudflare_stream');

        $metadata = 'maxDurationSeconds '.base64_encode((string) $config['max_duration_seconds']);

        // Presence of this key (any value) is what actually requires a
        // signed token at playback time on Cloudflare's side — see
        // signPlaybackUrl() below. Only set once a signing key exists: a
        // video uploaded with this flag but no key ever created to sign
        // its playback URL would be unplayable (the public URL would
        // start 403ing, and VideoResource's fallback has no token to
        // offer instead), so the two must land together.
        if ($config['signing_key_id'] !== null) {
            $metadata .= ',requiresignedurls '.base64_encode('true');
        }

        $response = Http::withToken($config['api_token'])
            ->baseUrl($this->apiBaseUrl())
            ->withHeaders([
                'Tus-Resumable' => '1.0.0',
                'Upload-Length' => (string) $fileSizeBytes,
                'Upload-Metadata' => $metadata,
            ])
            ->post('/stream?direct_user=true')
            ->throw();

        return new VideoUploadInitiation(
            uploadUrl: $response->header('Location'),
            providerVideoId: $response->header('stream-media-id'),
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
            // Cloudflare auto-generates this for every video (no separate
            // upload needed, same response as the rest of this method) but
            // its default frame is grabbed at t=0 — a black/blank frame for
            // any video with a fade-in intro (confirmed on several real
            // catalogue videos: identical 1651-byte black JPEG). `time=1s`
            // asks for a frame a second in instead, still safe for the
            // shortest videos on the platform (duration is always several
            // seconds at minimum).
            posterUrl: isset($result['thumbnail']) ? $result['thumbnail'].'?time=1s' : null,
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

    /**
     * "Enable MP4 downloads" — POST kicks off generation if it hasn't run
     * yet, and is safe to call repeatedly (Cloudflare just reports current
     * progress on later calls instead of restarting it). Never gated behind
     * a first-call/subsequent-call distinction on our side for that reason.
     */
    public function getDownloadState(Video $video): VideoDownloadState
    {
        $config = config('services.cloudflare_stream');

        $response = Http::withToken($config['api_token'])
            ->baseUrl($this->apiBaseUrl())
            ->post("/stream/{$video->provider_video_id}/downloads")
            ->throw()
            ->json();

        $default = $response['result']['default'] ?? null;
        $ready = ($default['status'] ?? null) === 'ready';

        return new VideoDownloadState(
            ready: $ready,
            url: $ready ? $default['url'] : null,
        );
    }

    /**
     * Cloudflare Stream's signed-URL token (documented at
     * developers.cloudflare.com/stream/viewing-videos/securing-your-stream)
     * is a JWT — RS256 over {alg,kid} + {sub,kid,exp} — signed with the
     * private key of a signing key created once via `POST /stream/keys`
     * (see infra/DEPLOY.md). No library pulled in for this: a two-segment
     * RS256 JWT is a handful of lines with openssl_sign(), and this is the
     * only place in the app that needs one.
     *
     * The token replaces the video's UID in its own playback_url (same
     * domain/path Cloudflare already gave us at upload time — not
     * hardcoding videodelivery.net vs a customer-<code>.cloudflarestream.com
     * subdomain, both are valid depending on account config) to produce a
     * URL that only plays until $exp, instead of forever.
     */
    public function signPlaybackUrl(Video $video): ?string
    {
        $config = config('services.cloudflare_stream');

        if ($config['signing_key_id'] === null || $config['signing_key_pem'] === null) {
            return null;
        }

        if ($video->playback_url === null || $video->provider_video_id === null) {
            return null;
        }

        $keyId = $config['signing_key_id'];
        $exp = now()->addSeconds($config['signed_url_ttl_seconds'])->timestamp;

        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'kid' => $keyId]));
        $payload = $this->base64UrlEncode(json_encode([
            'sub' => $video->provider_video_id,
            'kid' => $keyId,
            'exp' => $exp,
        ]));

        $signingInput = "{$header}.{$payload}";
        $signed = openssl_sign($signingInput, $signature, $config['signing_key_pem'], OPENSSL_ALGO_SHA256);

        if (! $signed) {
            return null;
        }

        $token = "{$signingInput}.".$this->base64UrlEncode($signature);

        return str_replace($video->provider_video_id, $token, $video->playback_url);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function apiBaseUrl(): string
    {
        $accountId = config('services.cloudflare_stream.account_id');

        return "https://api.cloudflare.com/client/v4/accounts/{$accountId}";
    }
}
