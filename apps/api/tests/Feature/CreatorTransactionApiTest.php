<?php

namespace Tests\Feature;

use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Payment\Actions\ConfirmPayment;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CreatorTransactionApiTest extends TestCase
{
    use RefreshDatabase;

    private function creditCreator(User $creator, int $amount, string $videoTitle = 'Ma vidéo'): void
    {
        Http::fake([
            '*/oauth/v3/token' => Http::response(['access_token' => 'fake-token'], 200),
            '*/transactionstatus*' => Http::response(['status' => 'SUCCESS'], 200),
        ]);

        $video = Video::factory()
            ->for($creator, 'creator')
            ->state(['status' => VideoStatus::Approved, 'price' => $amount, 'title' => $videoTitle])
            ->create();

        $payment = \App\Domain\Payment\Models\Payment::factory()->create([
            'video_id' => $video->id,
            'amount' => $amount,
            'provider_pay_token' => 'pay-token',
        ]);

        app(ConfirmPayment::class)($payment);
    }

    public function test_creator_can_see_their_transaction_history(): void
    {
        config(['platform.commission_rate' => 0.25]);
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $this->creditCreator($creator, 1000, 'Le rover Perseverance');

        $response = $this->actingAs($creator, 'sanctum')
            ->getJson('/api/creator/transactions')
            ->assertOk();

        $response->assertJsonPath('data.0.video_title', 'Le rover Perseverance');
        $response->assertJsonPath('data.0.gross_amount', 1000);
        $response->assertJsonPath('data.0.commission_amount', 250);
        $response->assertJsonPath('data.0.net_amount', 750);
        $response->assertJsonPath('data.0.status.value', 'succeeded');
    }

    public function test_a_creator_only_sees_their_own_transactions(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $otherCreator = User::factory()->create(['role' => UserRole::Creator]);
        $this->creditCreator($otherCreator, 1000);

        $response = $this->actingAs($creator, 'sanctum')
            ->getJson('/api/creator/transactions')
            ->assertOk();

        $response->assertJsonCount(0, 'data');
    }

    public function test_a_viewer_cannot_see_creator_transactions(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/creator/transactions')
            ->assertForbidden();
    }
}
