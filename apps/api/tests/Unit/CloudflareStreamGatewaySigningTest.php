<?php

namespace Tests\Unit;

use App\Domain\Video\Gateways\CloudflareStreamGateway;
use App\Domain\Video\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CloudflareStreamGateway::signPlaybackUrl() — the "URLs de lecture signées"
 * fix from the 2026-10-16 security audit (see App\Domain\Video\README.md).
 * No real Cloudflare account involved: the signing itself is pure local
 * crypto (RS256 over our own key pair), nothing here depends on what
 * Cloudflare does with the resulting token.
 */
class CloudflareStreamGatewaySigningTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_null_when_no_signing_key_is_configured(): void
    {
        config(['services.cloudflare_stream.signing_key_id' => null, 'services.cloudflare_stream.signing_key_pem' => null]);

        $video = Video::factory()->create([
            'provider_video_id' => 'cf-uid-abc123',
            'playback_url' => 'https://videodelivery.net/cf-uid-abc123/manifest/video.m3u8',
        ]);

        $this->assertNull((new CloudflareStreamGateway)->signPlaybackUrl($video));
    }

    public function test_returns_null_when_the_video_has_no_playback_url_yet(): void
    {
        [, $pem] = $this->generateKeyPair();
        config(['services.cloudflare_stream.signing_key_id' => 'key-1', 'services.cloudflare_stream.signing_key_pem' => $pem]);

        $video = Video::factory()->create(['provider_video_id' => null, 'playback_url' => null]);

        $this->assertNull((new CloudflareStreamGateway)->signPlaybackUrl($video));
    }

    public function test_replaces_the_video_uid_with_a_valid_short_lived_token(): void
    {
        [$publicKey, $privateKeyPem] = $this->generateKeyPair();
        config([
            'services.cloudflare_stream.signing_key_id' => 'key-1',
            'services.cloudflare_stream.signing_key_pem' => $privateKeyPem,
            'services.cloudflare_stream.signed_url_ttl_seconds' => 3600,
        ]);

        $video = Video::factory()->create([
            'provider_video_id' => 'cf-uid-abc123',
            'playback_url' => 'https://videodelivery.net/cf-uid-abc123/manifest/video.m3u8',
        ]);

        $signedUrl = (new CloudflareStreamGateway)->signPlaybackUrl($video);

        $this->assertNotNull($signedUrl);
        $this->assertStringStartsWith('https://videodelivery.net/', $signedUrl);
        $this->assertStringEndsWith('/manifest/video.m3u8', $signedUrl);
        $this->assertStringNotContainsString('cf-uid-abc123', $signedUrl);

        $token = str_replace(
            ['https://videodelivery.net/', '/manifest/video.m3u8'],
            '',
            $signedUrl,
        );
        [$header, $payload, $signature] = explode('.', $token);

        $decodedHeader = json_decode($this->base64UrlDecode($header), true);
        $decodedPayload = json_decode($this->base64UrlDecode($payload), true);

        $this->assertSame('RS256', $decodedHeader['alg']);
        $this->assertSame('key-1', $decodedHeader['kid']);
        $this->assertSame('cf-uid-abc123', $decodedPayload['sub']);
        $this->assertSame('key-1', $decodedPayload['kid']);
        $this->assertEqualsWithDelta(now()->addSeconds(3600)->timestamp, $decodedPayload['exp'], 5);

        $verified = openssl_verify(
            "{$header}.{$payload}",
            $this->base64UrlDecode($signature),
            $publicKey,
            OPENSSL_ALGO_SHA256,
        );
        $this->assertSame(1, $verified, 'the token must be signed by the configured private key');
    }

    /**
     * @return array{0: string, 1: string} public key PEM, private key PEM
     */
    private function generateKeyPair(): array
    {
        $resource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($resource, $privateKeyPem);
        $publicKey = openssl_pkey_get_details($resource)['key'];

        return [$publicKey, $privateKeyPem];
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(str_pad(strtr($value, '-_', '+/'), strlen($value) % 4 === 0 ? strlen($value) : strlen($value) + (4 - strlen($value) % 4), '='));
    }
}
