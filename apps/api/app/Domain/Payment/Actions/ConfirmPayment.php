<?php

namespace App\Domain\Payment\Actions;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\LedgerEntry;
use App\Domain\Payment\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent: safe to call multiple times for the same payment (Orange may
 * ping notif_url more than once, and network retries happen). The gateway
 * call itself isn't inside the lock/transaction below (no reason to hold a
 * row lock across a slow HTTP round-trip) — only the "is this still
 * pending, and if not, credit the ledger" part needs to be atomic, since
 * that's the part with a side effect that isn't safe to repeat.
 */
class ConfirmPayment
{
    public function __construct(private readonly PaymentGateway $gateway)
    {
    }

    public function __invoke(Payment $payment): Payment
    {
        if ($payment->status !== PaymentStatus::Pending) {
            return $payment;
        }

        $status = $this->gateway->verifyStatus($payment);

        if ($status === PaymentStatus::Pending) {
            return $payment;
        }

        return DB::transaction(function () use ($payment, $status) {
            // Re-fetch under a row lock: two concurrent calls for the same
            // payment (webhook fired twice, or racing the success-page poll)
            // both reach this point believing the payment is still Pending —
            // without the lock+recheck, both would pass and both would
            // create a LedgerEntry, double-crediting the creator for one
            // sale. lockForUpdate() makes the second transaction wait for
            // the first to commit, then its own re-check below sees the
            // already-Succeeded status and turns into a no-op.
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PaymentStatus::Pending) {
                return $locked;
            }

            $locked->update([
                'status' => $status,
                'confirmed_at' => now(),
            ]);

            if ($status === PaymentStatus::Succeeded) {
                $this->recordLedgerEntry($locked);
            }

            return $locked->fresh();
        });
    }

    private function recordLedgerEntry(Payment $payment): void
    {
        $commissionRate = config('platform.commission_rate');
        $commission = (int) round($payment->amount * $commissionRate);

        LedgerEntry::create([
            'creator_id' => $payment->video->creator_id,
            'payment_id' => $payment->id,
            'type' => 'sale',
            'gross_amount' => $payment->amount,
            'commission_amount' => $commission,
            'net_amount' => $payment->amount - $commission,
        ]);
    }
}
