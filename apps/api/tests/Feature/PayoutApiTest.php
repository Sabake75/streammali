<?php

namespace Tests\Feature;

use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Payment\Actions\ConfirmPayment;
use App\Domain\Payment\Enums\PayoutStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Filament\Resources\Payouts\Pages\EditPayout;
use App\Filament\Resources\Payouts\Pages\ListPayouts;
use App\Filament\Resources\Payouts\Tables\PayoutsTable;
use App\Models\User;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class PayoutApiTest extends TestCase
{
    use RefreshDatabase;

    private function creditCreator(User $creator, int $amount): void
    {
        Http::fake([
            '*/checkout-invoice/confirm/*' => Http::response(['status' => 'completed'], 200),
        ]);

        $video = Video::factory()
            ->for($creator, 'creator')
            ->state(['status' => VideoStatus::Approved, 'price' => $amount])
            ->create();

        $payment = Payment::factory()->create([
            'video_id' => $video->id,
            'amount' => $amount,
            'provider_pay_token' => 'pay-token',
        ]);

        app(ConfirmPayment::class)($payment);
    }

    public function test_a_successful_payment_credits_the_creators_ledger_at_the_configured_commission_rate(): void
    {
        config(['platform.commission_rate' => 0.25]);
        $creator = User::factory()->create(['role' => UserRole::Creator]);

        $this->creditCreator($creator, 1000);

        $this->assertDatabaseHas('ledger_entries', [
            'creator_id' => $creator->id,
            'gross_amount' => 1000,
            'commission_amount' => 250,
            'net_amount' => 750,
        ]);
    }

    public function test_creator_can_see_their_available_balance(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $this->creditCreator($creator, 20000);

        $response = $this->actingAs($creator, 'sanctum')
            ->getJson('/api/creator/balance')
            ->assertOk();

        $response->assertJsonPath('available_balance', 15000);
        $response->assertJsonPath('minimum_payout_amount', 10000);
    }

    public function test_creator_can_request_a_payout_above_the_minimum(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $this->creditCreator($creator, 20000);

        $response = $this->actingAs($creator, 'sanctum')
            ->postJson('/api/creator/payouts', [
                'amount' => 15000,
                'destination_msisdn' => '+223 76 00 00 00',
            ])
            ->assertCreated();

        $response->assertJsonPath('status.value', 'pending');

        $this->assertDatabaseHas('payouts', [
            'creator_id' => $creator->id,
            'amount' => 15000,
            'status' => 'pending',
        ]);
    }

    public function test_payout_request_below_the_minimum_is_rejected(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $this->creditCreator($creator, 20000);

        $this->actingAs($creator, 'sanctum')
            ->postJson('/api/creator/payouts', [
                'amount' => 5000,
                'destination_msisdn' => '+223 76 00 00 00',
            ])
            ->assertStatus(422);
    }

    public function test_payout_request_above_available_balance_is_rejected(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $this->creditCreator($creator, 10000);

        $this->actingAs($creator, 'sanctum')
            ->postJson('/api/creator/payouts', [
                'amount' => 50000,
                'destination_msisdn' => '+223 76 00 00 00',
            ])
            ->assertStatus(422);
    }

    public function test_a_pending_payout_reserves_the_balance_against_a_second_request(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $this->creditCreator($creator, 20000);

        $this->actingAs($creator, 'sanctum')
            ->postJson('/api/creator/payouts', ['amount' => 15000, 'destination_msisdn' => '+223 76 00 00 00'])
            ->assertCreated();

        $this->actingAs($creator, 'sanctum')
            ->postJson('/api/creator/payouts', ['amount' => 15000, 'destination_msisdn' => '+223 76 00 00 00'])
            ->assertStatus(422);
    }

    public function test_a_viewer_cannot_request_a_payout(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        $this->actingAs($viewer, 'sanctum')
            ->postJson('/api/creator/payouts', ['amount' => 10000, 'destination_msisdn' => '+223 76 00 00 00'])
            ->assertForbidden();
    }

    public function test_moderator_can_view_pending_payouts_in_the_back_office(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $this->creditCreator($creator, 20000);
        $creator->payouts()->create([
            'amount' => 15000,
            'destination_msisdn' => '+223 76 00 00 00',
            'status' => PayoutStatus::Pending,
        ]);

        $this->actingAs($moderator)
            ->get('/moderation/payouts')
            ->assertOk()
            ->assertSee('15000');
    }

    public function test_moderator_can_mark_a_payout_as_paid(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $this->creditCreator($creator, 20000);
        $payout = $creator->payouts()->create([
            'amount' => 15000,
            'destination_msisdn' => '+223 76 00 00 00',
            'status' => PayoutStatus::Pending,
        ]);

        $payout->update(['status' => PayoutStatus::Paid, 'processed_at' => now()]);

        $this->assertSame(PayoutStatus::Paid, $payout->fresh()->status);
        $this->assertNotNull($payout->fresh()->processed_at);
    }

    public function test_the_payouts_list_has_no_bulk_delete_action(): void
    {
        // A deleted payout loses the trace of a real withdrawal request —
        // "Rejeter" is the intended tool for a payout the moderator refuses.
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);

        Livewire::actingAs($moderator)
            ->test(ListPayouts::class)
            ->assertTableBulkActionDoesNotExist('delete');
    }

    public function test_the_edit_payout_page_has_no_delete_action(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $payout = $creator->payouts()->create([
            'amount' => 15000,
            'destination_msisdn' => '+223 76 00 00 00',
            'status' => PayoutStatus::Pending,
        ]);

        Livewire::actingAs($moderator)
            ->test(EditPayout::class, ['record' => $payout->getKey()])
            ->assertActionDoesNotExist('delete');
    }

    public function test_the_payouts_list_disables_the_default_click_to_edit_behavior(): void
    {
        // Same bug/fix as UsersTable/VideosTable: without ->recordUrl(null),
        // clicking a cell like "Numéro Mobile Money" opened the edit form.
        $table = PayoutsTable::configure(new Table(new ListPayouts));
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $payout = $creator->payouts()->create([
            'amount' => 15000,
            'destination_msisdn' => '+223 76 00 00 00',
            'status' => PayoutStatus::Pending,
        ]);

        $this->assertNull($table->getRecordUrl($payout));
    }
}
