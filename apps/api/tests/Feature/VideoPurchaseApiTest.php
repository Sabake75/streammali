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
            '*/checkout-invoice/create' => Http::response([
                'response_code' => '00',
                'response_text' => 'Invoice Created',
                'token' => 'invoice-token-abc123',
            ], 200),
        ]);

        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create(['price' => 25]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/videos/{$video->id}/purchase", [
                'payer_msisdn' => '+223 76 00 00 00',
            ])
            ->assertCreated();

        $response->assertJsonPath('payment_url', 'https://paydunya.com/checkout/invoice/invoice-token-abc123');
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

    public function test_guest_cannot_list_purchases(): void
    {
        $this->getJson('/api/purchases')->assertUnauthorized();
    }

    public function test_viewer_library_exposes_the_purchase_receipt_detail(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create(['price' => 250]);

        $video->payments()->create([
            'buyer_id' => $viewer->id,
            'amount' => 250,
            'order_reference' => 'receipt-order-ref',
            'status' => PaymentStatus::Succeeded,
            'confirmed_at' => '2026-08-20 10:00:00',
        ]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/purchases')
            ->assertOk();

        $response->assertJsonPath('data.0.purchase.amount', 250);
        $response->assertJsonPath('data.0.purchase.order_reference', 'receipt-order-ref');
        $response->assertJsonPath('data.0.purchase.purchased_at', '2026-08-20T10:00:00.000000Z');
    }

    public function test_catalogue_and_favorites_never_expose_the_purchase_block(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create();

        $video->payments()->create([
            'buyer_id' => $viewer->id,
            'amount' => $video->price,
            'order_reference' => 'unrelated-context-order',
            'status' => PaymentStatus::Succeeded,
            'confirmed_at' => now(),
        ]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/videos')
            ->assertOk();

        $response->assertJsonMissingPath('data.0.purchase');
    }

    public function test_viewer_library_only_lists_successfully_purchased_videos(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        $purchased = Video::factory()->approved()->create(['title' => 'Achetée']);
        $purchased->payments()->create([
            'buyer_id' => $viewer->id,
            'amount' => $purchased->price,
            'order_reference' => 'order-purchased',
            'status' => PaymentStatus::Succeeded,
            'confirmed_at' => now(),
        ]);

        $pending = Video::factory()->approved()->create(['title' => 'Paiement en attente']);
        $pending->payments()->create([
            'buyer_id' => $viewer->id,
            'amount' => $pending->price,
            'order_reference' => 'order-pending',
            'status' => PaymentStatus::Pending,
        ]);

        $notPurchased = Video::factory()->approved()->create(['title' => 'Jamais achetée']);

        $otherViewer = User::factory()->create(['role' => UserRole::Viewer]);
        $someoneElses = Video::factory()->approved()->create(['title' => 'Achetée par un autre']);
        $someoneElses->payments()->create([
            'buyer_id' => $otherViewer->id,
            'amount' => $someoneElses->price,
            'order_reference' => 'order-other',
            'status' => PaymentStatus::Succeeded,
            'confirmed_at' => now(),
        ]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/purchases')
            ->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $purchased->id);
        $response->assertJsonPath('data.0.purchased', true);
    }

    public function test_viewer_library_orders_by_purchase_date_not_video_creation_date(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        $olderVideoBoughtRecently = Video::factory()->approved()->create([
            'title' => 'Vieux film, acheté aujourd\'hui',
            'created_at' => now()->subYear(),
        ]);
        $olderVideoBoughtRecently->payments()->create([
            'buyer_id' => $viewer->id,
            'amount' => $olderVideoBoughtRecently->price,
            'order_reference' => 'order-recent-purchase',
            'status' => PaymentStatus::Succeeded,
            'confirmed_at' => now(),
        ]);

        $newerVideoBoughtLongAgo = Video::factory()->approved()->create([
            'title' => 'Film récent, acheté il y a longtemps',
            'created_at' => now(),
        ]);
        $newerVideoBoughtLongAgo->payments()->create([
            'buyer_id' => $viewer->id,
            'amount' => $newerVideoBoughtLongAgo->price,
            'order_reference' => 'order-old-purchase',
            'status' => PaymentStatus::Succeeded,
            'confirmed_at' => now()->subMonth(),
        ]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/purchases')
            ->assertOk();

        $response->assertJsonPath('data.0.id', $olderVideoBoughtRecently->id);
        $response->assertJsonPath('data.1.id', $newerVideoBoughtLongAgo->id);
    }
}
