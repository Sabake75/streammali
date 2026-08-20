<?php

namespace App\Domain\Payment\Models;

use App\Domain\Payment\Enums\PayoutStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'creator_id',
    'amount',
    'destination_msisdn',
    'status',
    'rejection_reason',
    'processed_at',
])]
class Payout extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'status' => PayoutStatus::class,
            'processed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
