<?php

namespace App\Domain\Payment\Actions;

use App\Domain\Payment\Enums\PayoutStatus;
use App\Domain\Payment\Models\LedgerEntry;
use App\Domain\Payment\Models\Payout;
use App\Models\User;

class GetCreatorBalance
{
    /**
     * Total earned minus what's already paid out or reserved by a pending
     * request — this is what's actually available for a new payout.
     */
    public function __invoke(User $creator): int
    {
        $earned = (int) LedgerEntry::where('creator_id', $creator->id)->sum('net_amount');

        $reserved = (int) Payout::where('creator_id', $creator->id)
            ->whereIn('status', [PayoutStatus::Pending, PayoutStatus::Paid])
            ->sum('amount');

        return $earned - $reserved;
    }
}
