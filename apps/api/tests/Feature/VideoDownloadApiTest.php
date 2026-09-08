<?php

namespace Tests\Feature;

use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Video\Enums\VideoSourceStatus;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VideoDownloadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_request_a_download(): void
    {
        $video = Video::factory()->approved()->create(['source_status' => VideoSourceStatus::Ready]);

        $this->postJson("/api/videos/{$video->id}/download")->assertUnauthorized();
    }

    public function test_a_viewer_who_has_not_purchased_the_video_cannot_download_it(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create(['source_status' => VideoSourceStatus::Ready]);

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/videos/{$video->id}/download")
            ->assertForbidden();
    }

    public function test_download_is_refused_while_the_source_file_is_not_ready(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create(['source_status' => VideoSourceStatus::Processing]);
        $this->purchase($viewer, $video);

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/videos/{$video->id}/download")
            ->assertStatus(409);
    }

    public function test_a_viewer_who_purchased_the_video_gets_a_processing_status_while_cloudflare_prepares_it(): void
    {
        Http::fake([
            '*/downloads' => Http::response([
                'success' => true,
                'result' => ['default' => ['status' => 'inprogress', 'percentComplete' => '12.5']],
            ], 200),
        ]);

        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create([
            'source_status' => VideoSourceStatus::Ready,
            'provider_video_id' => 'cf-uid-abc123',
        ]);
        $this->purchase($viewer, $video);

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/videos/{$video->id}/download")
            ->assertOk()
            ->assertJson(['status' => 'processing', 'url' => null]);
    }

    public function test_a_viewer_who_purchased_the_video_gets_the_download_url_once_ready(): void
    {
        Http::fake([
            '*/downloads' => Http::response([
                'success' => true,
                'result' => [
                    'default' => [
                        'status' => 'ready',
                        'url' => 'https://videodelivery.net/cf-uid-abc123/downloads/default.mp4',
                    ],
                ],
            ], 200),
        ]);

        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create([
            'source_status' => VideoSourceStatus::Ready,
            'provider_video_id' => 'cf-uid-abc123',
        ]);
        $this->purchase($viewer, $video);

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/videos/{$video->id}/download")
            ->assertOk()
            ->assertJson([
                'status' => 'ready',
                'url' => 'https://videodelivery.net/cf-uid-abc123/downloads/default.mp4',
            ]);
    }

    private function purchase(User $viewer, Video $video): void
    {
        Payment::factory()->create([
            'buyer_id' => $viewer->id,
            'video_id' => $video->id,
            'status' => PaymentStatus::Succeeded,
        ]);
    }
}
