<?php

namespace Tests\Feature;

use App\Domain\Video\Enums\VideoSourceStatus;
use App\Domain\Video\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudflareStreamWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_refreshes_the_video_once_cloudflare_reports_it_ready(): void
    {
        Http::fake([
            '*/stream/cf-uid-abc123' => Http::response([
                'success' => true,
                'result' => [
                    'status' => ['state' => 'ready'],
                    'playback' => ['hls' => 'https://videodelivery.net/cf-uid-abc123/manifest/video.m3u8'],
                ],
            ], 200),
        ]);

        $video = Video::factory()->create([
            'source_status' => VideoSourceStatus::Processing,
            'provider_video_id' => 'cf-uid-abc123',
        ]);

        $this->postJson('/api/webhooks/cloudflare-stream', [
            'uid' => 'cf-uid-abc123',
            'status' => ['state' => 'ready'],
        ])->assertOk();

        $this->assertSame(VideoSourceStatus::Ready, $video->fresh()->source_status);
        $this->assertSame(
            'https://videodelivery.net/cf-uid-abc123/manifest/video.m3u8',
            $video->fresh()->playback_url,
        );
    }

    public function test_webhook_does_not_trust_the_payloads_reported_status(): void
    {
        Http::fake([
            '*/stream/cf-uid-abc123' => Http::response([
                'success' => true,
                'result' => [
                    'status' => ['state' => 'processing'],
                    'playback' => null,
                ],
            ], 200),
        ]);

        $video = Video::factory()->create([
            'source_status' => VideoSourceStatus::Processing,
            'provider_video_id' => 'cf-uid-abc123',
        ]);

        $this->postJson('/api/webhooks/cloudflare-stream', [
            'uid' => 'cf-uid-abc123',
            'status' => ['state' => 'ready'],
        ])->assertOk();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/stream/cf-uid-abc123'));
        $this->assertSame(VideoSourceStatus::Processing, $video->fresh()->source_status);
    }

    public function test_webhook_for_an_unknown_video_returns_404(): void
    {
        $this->postJson('/api/webhooks/cloudflare-stream', [
            'uid' => 'does-not-exist',
        ])->assertNotFound();
    }
}
