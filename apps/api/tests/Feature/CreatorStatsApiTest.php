<?php

namespace Tests\Feature;

use App\Domain\Payment\Actions\ConfirmPayment;
use App\Domain\Payment\Models\Payment;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CreatorStatsApiTest extends TestCase
{
    use RefreshDatabase;

    private function sellVideo(Video $video, int $amount): void
    {
        Http::fake([
            '*/checkout-invoice/confirm/*' => Http::response(['status' => 'completed'], 200),
        ]);

        $payment = Payment::factory()->create([
            'video_id' => $video->id,
            'amount' => $amount,
            'provider_pay_token' => 'pay-token-' . uniqid(),
        ]);

        app(ConfirmPayment::class)($payment);
    }

    public function test_recording_a_view_increments_the_video_view_count(): void
    {
        $video = Video::factory()->approved()->create();

        $this->postJson("/api/videos/{$video->id}/view")->assertOk();
        $this->postJson("/api/videos/{$video->id}/view")->assertOk();

        $this->assertSame(2, $video->fresh()->views_count);
    }

    public function test_fetching_a_video_does_not_by_itself_increment_its_view_count(): void
    {
        $video = Video::factory()->approved()->create();

        $this->getJson("/api/videos/{$video->id}")->assertOk();

        $this->assertSame(0, $video->fresh()->views_count);
    }

    public function test_creator_can_see_per_video_stats(): void
    {
        config(['platform.commission_rate' => 0.25]);
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $video = Video::factory()->approved()->for($creator, 'creator')->create(['price' => 1000]);

        $this->postJson("/api/videos/{$video->id}/view")->assertOk();
        $this->sellVideo($video, 1000);

        $response = $this->actingAs($creator, 'sanctum')
            ->getJson('/api/creator/stats')
            ->assertOk();

        $response->assertJsonPath('videos.0.id', $video->id);
        $response->assertJsonPath('videos.0.views_count', 1);
        $response->assertJsonPath('videos.0.purchases_count', 1);
        $response->assertJsonPath('videos.0.revenue', 750);

        $response->assertJsonPath('totals.views', 1);
        $response->assertJsonPath('totals.purchases', 1);
        $response->assertJsonPath('totals.revenue', 750);
    }

    public function test_stats_include_a_fourteen_day_revenue_timeseries_with_todays_sale(): void
    {
        config(['platform.commission_rate' => 0.25]);
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $video = Video::factory()->approved()->for($creator, 'creator')->create(['price' => 2000]);

        $this->sellVideo($video, 2000);

        $response = $this->actingAs($creator, 'sanctum')
            ->getJson('/api/creator/stats')
            ->assertOk();

        $timeseries = $response->json('timeseries');
        $this->assertCount(14, $timeseries);
        $this->assertSame(now()->toDateString(), $timeseries[13]['date']);
        $this->assertSame(1500, $timeseries[13]['revenue']);
    }

    public function test_a_video_with_no_sales_reports_zero_revenue_not_missing(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        Video::factory()->approved()->for($creator, 'creator')->create();

        $response = $this->actingAs($creator, 'sanctum')
            ->getJson('/api/creator/stats')
            ->assertOk();

        $response->assertJsonPath('videos.0.revenue', 0);
        $response->assertJsonPath('videos.0.purchases_count', 0);
    }

    public function test_a_viewer_cannot_access_creator_stats(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/creator/stats')
            ->assertForbidden();
    }

    public function test_a_creator_only_sees_their_own_videos_in_stats(): void
    {
        $creatorA = User::factory()->create(['role' => UserRole::Creator]);
        $creatorB = User::factory()->create(['role' => UserRole::Creator]);
        Video::factory()->approved()->for($creatorB, 'creator')->create();

        $response = $this->actingAs($creatorA, 'sanctum')
            ->getJson('/api/creator/stats')
            ->assertOk();

        $response->assertJsonCount(0, 'videos');
    }
}
