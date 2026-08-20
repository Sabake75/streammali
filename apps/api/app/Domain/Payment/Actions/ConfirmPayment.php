<?php

namespace App\Domain\Payment\Actions;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;

/**
 * Idempotent: safe to call multiple times for the same payment (Orange may
 * ping notif_url more than once, and network retries happen).
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

        $payment->update([
            'status' => $status,
            'confirmed_at' => now(),
        ]);

        return $payment->fresh();
    }
}
