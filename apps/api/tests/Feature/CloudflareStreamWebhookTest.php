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
            '*/stream/clip' => Http::response([
                'success' => true,
                'result' => [
                    'uid' => 'cf-uid-preview-abc123',
                    'playback' => ['hls' => 'https://videodelivery.net/cf-uid-preview-abc123/manifest/video.m3u8'],
                ],
            ], 200),
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

    public function test_webhook_creates_a_preview_clip_once_the_video_is_ready(): void
    {
        Http::fake([
            '*/stream/clip' => Http::response([
                'success' => true,
                'result' => [
                    'uid' => 'cf-uid-preview-abc123',
                    'playback' => ['hls' => 'https://videodelivery.net/cf-uid-preview-abc123/manifest/video.m3u8'],
                ],
            ], 200),
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
            'duration_seconds' => 600,
        ]);

        $this->postJson('/api/webhooks/cloudflare-stream', ['uid' => 'cf-uid-abc123'])->assertOk();

        $fresh = $video->fresh();
        $this->assertSame('cf-uid-preview-abc123', $fresh->preview_provider_video_id);
        $this->assertSame(
            'https://videodelivery.net/cf-uid-preview-abc123/manifest/video.m3u8',
            $fresh->preview_playback_url,
        );
        Http::assertSent(fn ($request) => str_contains($request->url(), '/stream/clip')
            && $request['clippedFromVideoUID'] === 'cf-uid-abc123'
            && $request['endTimeSeconds'] === 45);
    }

    public function test_webhook_does_not_recreate_a_preview_clip_that_already_exists(): void
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
            'preview_provider_video_id' => 'already-there',
            'preview_playback_url' => 'https://videodelivery.net/already-there/manifest/video.m3u8',
        ]);

        $this->postJson('/api/webhooks/cloudflare-stream', ['uid' => 'cf-uid-abc123'])->assertOk();

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/stream/clip'));
        $this->assertSame('already-there', $video->fresh()->preview_provider_video_id);
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
