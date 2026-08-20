<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\Data\PaymentInitiationResult;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;

interface PaymentGateway
{
    /**
     * Start a payment with the provider and return the URL the buyer must
     * complete it on (USSD push / in-app confirmation), plus the token
     * needed to later query the transaction status.
     */
    public function initiate(Payment $payment): PaymentInitiationResult;

    /**
     * Ask the provider for the authoritative status of a payment.
     *
     * Never trust a webhook payload's status directly — providers' notif
     * callbacks are typically unsigned pings, not proof of payment. Use
     * this to confirm status server-to-server before marking a payment
     * succeeded.
     */
    public function verifyStatus(Payment $payment): PaymentStatus;
}
