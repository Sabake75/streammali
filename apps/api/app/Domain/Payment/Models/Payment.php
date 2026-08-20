<?php

namespace App\Domain\Payment\Models;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Video\Models\Video;
use App\Models\User;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'buyer_id',
    'video_id',
    'amount',
    'provider',
    'payer_msisdn',
    'order_reference',
    'provider_pay_token',
    'provider_transaction_id',
    'status',
    'raw_webhook_payload',
    'confirmed_at',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'raw_webhook_payload' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }
}
