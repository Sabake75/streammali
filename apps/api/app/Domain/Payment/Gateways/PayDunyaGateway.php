<?php

namespace App\Domain\Payment\Gateways;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Data\PaymentInitiationResult;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Exceptions\PaymentGatewayException;
use App\Domain\Payment\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * PayDunya Checkout Invoice integration (aggregator: Orange Money, Moov
 * Money, cards… behind one redirect page, rather than talking to Orange
 * Money directly).
 *
 * Implémentation basée sur le contrat documenté publiquement pour l'API
 * "Checkout Invoice" de PayDunya : `checkout-invoice/create` renvoie un
 * `token`, le client complète le paiement sur
 * `https://paydunya.com/checkout/invoice/{token}`, et le statut se
 * revérifie côté serveur via `checkout-invoice/confirm/{token}`. **À
 * vérifier contre un vrai compte marchand** (mêmes réserves que
 * OrangeMoneyGateway/CloudflareStreamGateway) — en particulier la forme
 * exacte du payload IPN envoyé à `callback_url`.
 */
class PayDunyaGateway implements PaymentGateway
{
    public function initiate(Payment $payment): PaymentInitiationResult
    {
        $config = config('services.paydunya');

        $response = Http::withHeaders($this->authHeaders($config))
            ->baseUrl($config['base_url'])
            ->post('/checkout-invoice/create', [
                'invoice' => [
                    'total_amount' => $payment->amount,
                    'description' => "StreamMali #{$payment->id}",
                ],
                'store' => [
                    'name' => 'StreamMali',
                ],
                'actions' => [
                    'cancel_url' => $config['cancel_url'],
                    'return_url' => $config['return_url'],
                    'callback_url' => $config['callback_url'],
                ],
                'custom_data' => [
                    'order_reference' => $payment->order_reference,
                ],
            ])
            ->throw()
            ->json();

        if (($response['response_code'] ?? null) !== '00') {
            throw new PaymentGatewayException(
                'PayDunya invoice creation failed: '.($response['response_text'] ?? 'unknown error')
            );
        }

        $token = $response['token'];

        return new PaymentInitiationResult(
            paymentUrl: "https://paydunya.com/checkout/invoice/{$token}",
            payToken: $token,
        );
    }

    public function verifyStatus(Payment $payment): PaymentStatus
    {
        if ($payment->provider_pay_token === null) {
            throw new RuntimeException("Payment #{$payment->id} has no provider_pay_token to verify against.");
        }

        $config = config('services.paydunya');

        $response = Http::withHeaders($this->authHeaders($config))
            ->baseUrl($config['base_url'])
            ->get("/checkout-invoice/confirm/{$payment->provider_pay_token}")
            ->throw()
            ->json();

        return match ($response['status'] ?? null) {
            'completed' => PaymentStatus::Succeeded,
            'cancelled', 'declined' => PaymentStatus::Failed,
            default => PaymentStatus::Pending,
        };
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(array $config): array
    {
        return [
            'PAYDUNYA-MASTER-KEY' => $config['master_key'],
            'PAYDUNYA-PRIVATE-KEY' => $config['private_key'],
            'PAYDUNYA-PUBLIC-KEY' => $config['public_key'],
            'PAYDUNYA-TOKEN' => $config['token'],
            'Content-Type' => 'application/json',
        ];
    }
}
