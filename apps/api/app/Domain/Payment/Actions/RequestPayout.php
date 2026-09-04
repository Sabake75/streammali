<?php

namespace App\Domain\Payment\Actions;

use App\Domain\Payment\Enums\PayoutStatus;
use App\Domain\Payment\Exceptions\PayoutException;
use App\Domain\Payment\Models\Payout;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class RequestPayout
{
    public function __construct(private readonly GetCreatorBalance $getCreatorBalance)
    {
    }

    public function __invoke(User $creator, int $amount, string $destinationMsisdn): Payout
    {
        $minimum = config('platform.minimum_payout_amount');

        if ($amount < $minimum) {
            throw new PayoutException("Le montant minimum de retrait est de {$minimum} FCFA.");
        }

        // Without this lock, two concurrent requests (double-tap, or a
        // scripted retry) can both read the same available balance before
        // either Payout row exists, both pass the check, and together
        // reserve more than the creator actually has — GetCreatorBalance
        // only subtracts *existing* pending/paid payouts, so it can't catch
        // a request that hasn't been persisted yet. Serializing per-creator
        // closes that window; Cache::lock works the same way across every
        // cache driver this app runs on (array in tests, database/redis in
        // prod), unlike a DB-level row lock which has nothing to lock here
        // (the balance is an aggregate, not a single row).
        return Cache::lock("payout-request:{$creator->id}", 10)->block(5, function () use ($creator, $amount, $destinationMsisdn) {
            $available = ($this->getCreatorBalance)($creator);

            if ($amount > $available) {
                throw new PayoutException("Solde disponible insuffisant ({$available} FCFA).");
            }

            return Payout::create([
                'creator_id' => $creator->id,
                'amount' => $amount,
                'destination_msisdn' => $destinationMsisdn,
                'status' => PayoutStatus::Pending,
            ]);
        });
    }
}
