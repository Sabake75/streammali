<?php

namespace App\Domain\Payment\Actions;

use App\Domain\Payment\Enums\PayoutStatus;
use App\Domain\Payment\Exceptions\PayoutException;
use App\Domain\Payment\Models\Payout;
use App\Models\User;

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
    }
}
