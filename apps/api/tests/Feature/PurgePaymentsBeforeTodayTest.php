<?php

namespace Tests\Feature;

use App\Domain\Payment\Models\LedgerEntry;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Models\Payout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgePaymentsBeforeTodayTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_reports_without_deleting_anything(): void
    {
        $payment = Payment::factory()->succeeded()->create(['created_at' => now()->subDay()]);
        LedgerEntry::create([
            'creator_id' => $payment->video->creator_id,
            'payment_id' => $payment->id,
            'gross_amount' => 100,
            'commission_amount' => 25,
            'net_amount' => 75,
        ]);

        $this->artisan('payments:purge-before-today')->assertSuccessful();

        $this->assertSame(1, Payment::count());
        $this->assertSame(1, LedgerEntry::count());
    }

    public function test_force_deletes_payments_and_their_ledger_entries_created_before_today(): void
    {
        $old = Payment::factory()->succeeded()->create(['created_at' => now()->subDay()]);
        LedgerEntry::create([
            'creator_id' => $old->video->creator_id,
            'payment_id' => $old->id,
            'gross_amount' => 100,
            'commission_amount' => 25,
            'net_amount' => 75,
        ]);

        $today = Payment::factory()->succeeded()->create(['created_at' => now()]);
        LedgerEntry::create([
            'creator_id' => $today->video->creator_id,
            'payment_id' => $today->id,
            'gross_amount' => 100,
            'commission_amount' => 25,
            'net_amount' => 75,
        ]);

        $this->artisan('payments:purge-before-today', ['--force' => true])->assertSuccessful();

        $this->assertSame(1, Payment::count());
        $this->assertSame($today->id, Payment::first()->id);
        $this->assertSame(1, LedgerEntry::count());
        $this->assertSame($today->id, LedgerEntry::first()->payment_id);
    }

    public function test_refuses_to_run_if_a_payout_already_exists_without_the_include_flag(): void
    {
        $payment = Payment::factory()->succeeded()->create(['created_at' => now()->subDay()]);
        $payout = Payout::create([
            'creator_id' => $payment->video->creator_id,
            'amount' => 10000,
            'destination_msisdn' => '+223 76 00 00 00',
        ]);
        $payout->forceFill(['created_at' => now()->subDay()])->save();

        $this->artisan('payments:purge-before-today', ['--force' => true])->assertFailed();

        $this->assertSame(1, Payment::count());
        $this->assertSame(1, Payout::count());
    }

    public function test_include_paid_payouts_flag_also_deletes_payouts_created_before_today(): void
    {
        $payment = Payment::factory()->succeeded()->create(['created_at' => now()->subDay()]);
        $payout = Payout::create([
            'creator_id' => $payment->video->creator_id,
            'amount' => 10000,
            'destination_msisdn' => '+223 76 00 00 00',
            'status' => 'paid',
        ]);
        $payout->forceFill(['created_at' => now()->subDay()])->save();

        $this->artisan('payments:purge-before-today', ['--force' => true, '--include-paid-payouts' => true])
            ->assertSuccessful();

        $this->assertSame(0, Payment::count());
        $this->assertSame(0, Payout::count());
    }
}
