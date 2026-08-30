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
        ]);

        $this->artisan('videos:create-missing-previews')->assertSuccessful();

        $fresh = $video->fresh();
        $this->assertSame('cf-uid-preview-abc123', $fresh->preview_provider_video_id);
        $this->assertSame(
            'https://videodelivery.net/cf-uid-preview-abc123/manifest/video.m3u8',
            $fresh->preview_playback_url,
        );
    }

    public function test_it_skips_videos_that_already_have_a_preview(): void
    {
        Http::fake();

        Video::factory()->create([
            'source_status' => VideoSourceStatus::Ready,
            'preview_provider_video_id' => 'already-there',
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
        ]);
        $other = Video::factory()->create([
            'source_status' => VideoSourceStatus::Ready,
            'provider_video_id' => 'cf-uid-other',
            'preview_provider_video_id' => null,
        ]);

        $this->artisan("videos:create-missing-previews {$target->id}")->assertSuccessful();

        $this->assertNotNull($target->fresh()->preview_provider_video_id);
        $this->assertNull($other->fresh()->preview_provider_video_id);
    }
}
