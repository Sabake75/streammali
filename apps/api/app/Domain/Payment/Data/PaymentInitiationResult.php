<?php

namespace App\Domain\Payment\Data;

final readonly class PaymentInitiationResult
{
    public function __construct(
        public string $paymentUrl,
        public string $payToken,
        public ?string $notifToken = null,
    ) {
    }
}
