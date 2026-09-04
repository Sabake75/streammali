<?php

namespace App\Domain\Payment\Actions;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Data\InitiatedPayment;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Video\Models\Video;
use App\Models\User;
use Illuminate\Support\Str;

class InitiatePayment
{
    public function __construct(private readonly PaymentGateway $gateway)
    {
    }

    public function __invoke(User $buyer, Video $video, string $payerMsisdn): InitiatedPayment
    {
        $payment = Payment::create([
            'buyer_id' => $buyer->id,
            'video_id' => $video->id,
            'amount' => $video->price,
            'payer_msisdn' => $payerMsisdn,
            // Orange Money caps order_id at 30 chars (see App\Domain\Payment\Gateways\OrangeMoneyGateway) —
            // a UUID (36 chars) doesn't fit, a ULID (26 chars) does.
            'order_reference' => (string) Str::ulid(),
            'status' => PaymentStatus::Pending,
        ]);

        $result = $this->gateway->initiate($payment);

        $payment->update(['provider_pay_token' => $result->payToken]);

        return new InitiatedPayment($payment, $result->paymentUrl);
    }
}
