<?php

namespace App\Domain\Payment\Gateways;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Data\PaymentInitiationResult;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Orange Money Web Payment integration.
 *
 * Implémentation basée sur le contrat documenté publiquement pour l'API
 * "Orange Money Web Payment" (OAuth2 client_credentials, puis un endpoint
 * d'initiation qui renvoie une URL de paiement + un pay_token, et un
 * endpoint de statut interrogé côté serveur). Les chemins exacts sous
 * `base_url` sont à confirmer avec la doc Orange Developer Center Mali une
 * fois les credentials marchand obtenus — voir config/services.php.
 */
class OrangeMoneyGateway implements PaymentGateway
{
    public function initiate(Payment $payment): PaymentInitiationResult
    {
        $config = config('services.orange_money');

        $response = Http::withToken($this->getAccessToken())
            ->baseUrl($this->apiBaseUrl())
            ->post('/webpayment', [
                'merchant_key' => $config['merchant_key'],
                'currency' => 'OUV', // Franc CFA (UEMOA) selon la nomenclature Orange Money Web Payment
                'order_id' => $payment->order_reference,
                'amount' => $payment->amount,
                'return_url' => $config['return_url'],
                'cancel_url' => $config['cancel_url'],
                'notif_url' => $config['notif_url'],
                'lang' => 'fr',
                'reference' => "StreamMali #{$payment->id}",
            ])
            ->throw()
            ->json();

        return new PaymentInitiationResult(
            paymentUrl: $response['payment_url'],
            payToken: $response['pay_token'],
        );
    }

    public function verifyStatus(Payment $payment): PaymentStatus
    {
        if ($payment->provider_pay_token === null) {
            throw new RuntimeException("Payment #{$payment->id} has no provider_pay_token to verify against.");
        }

        $response = Http::withToken($this->getAccessToken())
            ->baseUrl($this->apiBaseUrl())
            ->get('/transactionstatus', [
                'order_id' => $payment->order_reference,
                'amount' => $payment->amount,
                'pay_token' => $payment->provider_pay_token,
            ])
            ->throw()
            ->json();

        return match ($response['status'] ?? null) {
            'SUCCESS' => PaymentStatus::Succeeded,
            'FAILED', 'EXPIRED' => PaymentStatus::Failed,
            default => PaymentStatus::Pending,
        };
    }

    private function apiBaseUrl(): string
    {
        $config = config('services.orange_money');

        return "{$config['base_url']}/orange-money-webpay/{$config['country']}/v1";
    }

    private function getAccessToken(): string
    {
        $config = config('services.orange_money');

        return Cache::remember('orange_money.access_token', now()->addMinutes(25), function () use ($config) {
            $response = Http::asForm()
                ->withBasicAuth($config['client_id'], $config['client_secret'])
                ->post("{$config['base_url']}/oauth/v3/token", [
                    'grant_type' => 'client_credentials',
                ])
                ->throw()
                ->json();

            return $response['access_token'];
        });
    }
}
