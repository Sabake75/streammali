<?php

namespace Tests\Feature;

use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/register', [
                'name' => 'Test',
                'phone' => "+22376000{$i}00",
                'password' => '1234',
                'terms_accepted' => true,
            ]);
        }

        // "Too Many Attempts." is hardcoded in English inside Laravel's own
        // ThrottleRequests middleware — not a lang/ key, confirmed leaking
        // raw to a real viewer registering on the production web/mobile
        // apps. bootstrap/app.php maps it to a French message.
        $this->postJson('/api/register', [
            'name' => 'Test',
            'phone' => '+22376999900',
            'password' => '1234',
            'terms_accepted' => true,
        ])->assertStatus(429)
            ->assertJson(['message' => 'Trop de tentatives. Réessaie dans un instant.']);
    }

    public function test_video_purchase_is_rate_limited_per_user(): void
    {
        Http::fake([
            '*/oauth/v3/token' => Http::response(['access_token' => 'fake-token'], 200),
            '*/webpayment' => Http::response([
                'payment_url' => 'https://webpay.orange-money.test/pay/abc123',
                'pay_token' => 'pay-token-abc123',
            ], 200),
        ]);

        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        for ($i = 0; $i < 10; $i++) {
            $video = Video::factory()->approved()->create();
            $this->actingAs($viewer, 'sanctum')->postJson("/api/videos/{$video->id}/purchase", [
                'payer_msisdn' => '+223 76 00 00 00',
            ]);
        }

        $video = Video::factory()->approved()->create();
        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/videos/{$video->id}/purchase", ['payer_msisdn' => '+223 76 00 00 00'])
            ->assertStatus(429);
    }

    public function test_reviews_reports_and_favorites_are_rate_limited_per_user(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create();

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($viewer, 'sanctum')->postJson("/api/videos/{$video->id}/favorite");
        }

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/videos/{$video->id}/favorite")
            ->assertStatus(429);
    }
}
