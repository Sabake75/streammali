<?php

namespace Tests\Feature;

use App\Domain\Video\Enums\VideoSourceStatus;
use App\Domain\Video\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CreateMissingPreviewClipsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_preview_clip_for_a_ready_video_missing_one(): void
    {
        Http::fake([
            '*/stream/clip' => Http::response([
                'success' => true,
                'result' => [
                    'uid' => 'cf-uid-preview-abc123',
                    'playback' => ['hls' => 'https://videodelivery.net/cf-uid-preview-abc123/manifest/video.m3u8'],
                ],
            ], 200),
        ]);

        $video = Video::factory()->create([
            'source_status' => VideoSourceStatus::Ready,
            'provider_video_id' => 'cf-uid-abc123',
            'preview_provider_video_id' => null,
            // Already set — isolates this test to the preview path only.
            'poster_path' => 'https://example.com/poster.jpg',
        ]);

        $this->artisan('videos:create-missing-previews')->assertSuccessful();

        $fresh = $video->fresh();
        $this->assertSame('cf-uid-preview-abc123', $fresh->preview_provider_video_id);
        $this->assertSame(
            'https://videodelivery.net/cf-uid-preview-abc123/manifest/video.m3u8',
            $fresh->preview_playback_url,
        );
    }

    public function test_it_sets_a_poster_for_a_ready_video_missing_one(): void
    {
        Http::fake([
            '*/stream/cf-uid-abc123' => Http::response([
                'success' => true,
                'result' => [
                    'status' => ['state' => 'ready'],
                    'playback' => ['hls' => 'https://videodelivery.net/cf-uid-abc123/manifest/video.m3u8'],
                    'thumbnail' => 'https://customer-xyz.cloudflarestream.com/cf-uid-abc123/thumbnails/thumbnail.jpg',
                ],
            ], 200),
        ]);

        $video = Video::factory()->create([
            'source_status' => VideoSourceStatus::Ready,
            'provider_video_id' => 'cf-uid-abc123',
            // Already set — isolates this test to the poster path only.
            'preview_provider_video_id' => 'already-there',
            'poster_path' => null,
        ]);

        $this->artisan('videos:create-missing-previews')->assertSuccessful();

        $this->assertSame(
            'https://customer-xyz.cloudflarestream.com/cf-uid-abc123/thumbnails/thumbnail.jpg',
            $video->fresh()->poster_path,
        );
    }

    public function test_it_handles_a_video_missing_both_in_one_run(): void
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
                    'thumbnail' => 'https://customer-xyz.cloudflarestream.com/cf-uid-abc123/thumbnails/thumbnail.jpg',
                ],
            ], 200),
        ]);

        $video = Video::factory()->create([
            'source_status' => VideoSourceStatus::Ready,
            'provider_video_id' => 'cf-uid-abc123',
            'preview_provider_video_id' => null,
            'poster_path' => null,
        ]);

        $this->artisan('videos:create-missing-previews')->assertSuccessful();

        $fresh = $video->fresh();
        $this->assertSame('cf-uid-preview-abc123', $fresh->preview_provider_video_id);
        $this->assertSame(
            'https://customer-xyz.cloudflarestream.com/cf-uid-abc123/thumbnails/thumbnail.jpg',
            $fresh->poster_path,
        );
    }

    public function test_it_skips_videos_that_already_have_both(): void
    {
        Http::fake();

        Video::factory()->create([
            'source_status' => VideoSourceStatus::Ready,
            'preview_provider_video_id' => 'already-there',
            'poster_path' => 'https://example.com/poster.jpg',
        ]);

        $this->artisan('videos:create-missing-previews')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_it_skips_videos_that_are_not_ready(): void
    {
        Http::fake();

        Video::factory()->create([
            'source_status' => VideoSourceStatus::Processing,
            'preview_provider_video_id' => null,
            'poster_path' => null,
        ]);

        $this->artisan('videos:create-missing-previews')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_it_can_target_a_single_video_by_id(): void
    {
        Http::fake([
            '*/stream/clip' => Http::response([
                'success' => true,
                'result' => [
                    'uid' => 'cf-uid-preview-only-this-one',
                    'playback' => ['hls' => 'https://videodelivery.net/cf-uid-preview-only-this-one/manifest/video.m3u8'],
                ],
            ], 200),
        ]);

        $target = Video::factory()->create([
            'source_status' => VideoSourceStatus::Ready,
            'provider_video_id' => 'cf-uid-target',
            'preview_provider_video_id' => null,
            'poster_path' => 'https://example.com/poster.jpg',
        ]);
        $other = Video::factory()->create([
            'source_status' => VideoSourceStatus::Ready,
            'provider_video_id' => 'cf-uid-other',
            'preview_provider_video_id' => null,
            'poster_path' => 'https://example.com/poster.jpg',
        ]);

        $this->artisan("videos:create-missing-previews {$target->id}")->assertSuccessful();

        $this->assertNotNull($target->fresh()->preview_provider_video_id);
        $this->assertNull($other->fresh()->preview_provider_video_id);
    }
}
