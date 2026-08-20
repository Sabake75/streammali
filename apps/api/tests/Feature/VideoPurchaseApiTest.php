<?php

namespace Tests\Feature;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VideoPurchaseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_purchase_a_video(): void
    {
        $video = Video::factory()->approved()->create();

        $this->postJson("/api/videos/{$video->id}/purchase", [
            'payer_msisdn' => '+223 76 00 00 00',
        ])->assertUnauthorized();
    }

    public function test_authenticated_viewer_can_purchase_an_approved_video(): void
    {
        Http::fake([
            '*/oauth/v3/token' => Http::response(['access_token' => 'fake-token'], 200),
            '*/webpayment' => Http::response([
                'payment_url' => 'https://webpay.orange-money.test/pay/abc123',
                'pay_token' => 'pay-token-abc123',
            ], 200),
        ]);

        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create(['price' => 25]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/videos/{$video->id}/purchase", [
                'payer_msisdn' => '+223 76 00 00 00',
            ])
            ->assertCreated();

        $response->assertJsonPath('payment_url', 'https://webpay.orange-money.test/pay/abc123');
        $response->assertJsonPath('payment.status', 'pending');
        $response->assertJsonPath('payment.amount', 25);

        $this->assertDatabaseHas('payments', [
            'buyer_id' => $viewer->id,
            'video_id' => $video->id,
            'status' => 'pending',
        ]);
    }

    public function test_cannot_purchase_a_video_that_is_not_yet_approved(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->create();

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/videos/{$video->id}/purchase", [
                'payer_msisdn' => '+223 76 00 00 00',
            ])
            ->assertNotFound();
    }

    public function test_cannot_purchase_a_video_already_purchased(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create();

        $video->payments()->create([
            'buyer_id' => $viewer->id,
            'amount' => $video->price,
            'order_reference' => 'existing-order',
            'status' => PaymentStatus::Succeeded,
            'confirmed_at' => now(),
        ]);

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/videos/{$video->id}/purchase", [
                'payer_msisdn' => '+223 76 00 00 00',
            ])
            ->assertStatus(409);
    }
}
