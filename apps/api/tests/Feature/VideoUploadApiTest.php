<?php

namespace Tests\Feature;

use App\Domain\Video\Enums\VideoSourceStatus;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VideoUploadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_request_an_upload_url_for_their_video(): void
    {
        Http::fake([
            '*/stream/direct_upload' => Http::response([
                'success' => true,
                'result' => [
                    'uploadURL' => 'https://upload.videodelivery.net/abc123',
                    'uid' => 'cf-uid-abc123',
                ],
            ], 200),
        ]);

        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $video = Video::factory()->for($creator, 'creator')->create();

        $response = $this->actingAs($creator, 'sanctum')
            ->postJson("/api/creator/videos/{$video->id}/source")
            ->assertCreated();

        $response->assertJsonPath('upload_url', 'https://upload.videodelivery.net/abc123');
        $response->assertJsonPath('source_status', 'processing');

        $this->assertDatabaseHas('videos', [
            'id' => $video->id,
            'provider_video_id' => 'cf-uid-abc123',
            'source_status' => 'processing',
        ]);
    }

    public function test_cannot_request_an_upload_url_for_someone_elses_video(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $otherCreator = User::factory()->create(['role' => UserRole::Creator]);
        $video = Video::factory()->for($otherCreator, 'creator')->create();

        $this->actingAs($creator, 'sanctum')
            ->postJson("/api/creator/videos/{$video->id}/source")
            ->assertForbidden();
    }

    public function test_cannot_request_a_second_upload_url_while_one_is_already_processing(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $video = Video::factory()->for($creator, 'creator')->create(['source_status' => VideoSourceStatus::Processing]);

        $this->actingAs($creator, 'sanctum')
            ->postJson("/api/creator/videos/{$video->id}/source")
            ->assertStatus(409);
    }

    public function test_refreshing_status_updates_the_video_once_ready(): void
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

        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $video = Video::factory()->for($creator, 'creator')->create([
            'source_status' => VideoSourceStatus::Processing,
            'provider_video_id' => 'cf-uid-abc123',
        ]);

        $response = $this->actingAs($creator, 'sanctum')
            ->getJson("/api/creator/videos/{$video->id}/source")
            ->assertOk();

        $response->assertJsonPath('source_status.value', 'ready');
        $response->assertJsonPath('playback_url', 'https://videodelivery.net/cf-uid-abc123/manifest/video.m3u8');

        $this->assertSame(VideoSourceStatus::Ready, $video->fresh()->source_status);
    }

    public function test_a_purchased_and_ready_video_exposes_its_playback_url_in_the_catalogue(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create([
            'source_status' => VideoSourceStatus::Ready,
            'playback_url' => 'https://videodelivery.net/cf-uid-abc123/manifest/video.m3u8',
        ]);

        $video->payments()->create([
            'buyer_id' => $viewer->id,
            'amount' => $video->price,
            'order_reference' => 'order-1',
            'status' => \App\Domain\Payment\Enums\PaymentStatus::Succeeded,
            'confirmed_at' => now(),
        ]);

        $token = $viewer->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/videos/{$video->id}")
            ->assertOk();

        $response->assertJsonPath('data.playback_url', 'https://videodelivery.net/cf-uid-abc123/manifest/video.m3u8');
    }

    public function test_a_non_purchased_video_does_not_expose_its_playback_url(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create([
            'source_status' => VideoSourceStatus::Ready,
            'playback_url' => 'https://videodelivery.net/cf-uid-abc123/manifest/video.m3u8',
        ]);

        $token = $viewer->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/videos/{$video->id}")
            ->assertOk();

        $response->assertJsonMissingPath('data.playback_url');
    }

    public function test_a_guest_never_sees_a_playback_url(): void
    {
        $video = Video::factory()->approved()->create([
            'source_status' => VideoSourceStatus::Ready,
            'playback_url' => 'https://videodelivery.net/cf-uid-abc123/manifest/video.m3u8',
        ]);

        $response = $this->getJson("/api/videos/{$video->id}")->assertOk();

        $response->assertJsonMissingPath('data.playback_url');
        $response->assertJsonMissingPath('data.purchased');
    }
}
