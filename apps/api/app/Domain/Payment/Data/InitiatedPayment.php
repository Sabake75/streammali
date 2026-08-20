<?php

namespace App\Domain\Payment\Data;

use App\Domain\Payment\Models\Payment;

final readonly class InitiatedPayment
{
    public function __construct(
        public Payment $payment,
        public string $paymentUrl,
    ) {
    }
}
