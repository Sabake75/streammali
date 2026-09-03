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
 * OAuth2 client_credentials, puis un endpoint d'initiation qui renvoie une
 * URL de paiement + un pay_token, et un endpoint de statut interrogé côté
 * serveur. Vérifié en réel (2026-09-03) avec le compte sandbox "Orange Money
 * WebPay Dev" — deux pièges rencontrés :
 * - `merchant_key` ne prend PAS le point final affiché sur le Developer
 *   Center (`35ec1887`, pas `35ec1887.`) — avec le point, Orange renvoie une
 *   erreur "Invalid body field ... bad syntax".
 * - `return_url`/`cancel_url`/`notif_url` doivent être des URLs publiques —
 *   Orange rejette explicitement localhost/127.0.0.1 ("localhost and
 *   127.0.0.1 are not allowed"), donc impossible de tester le
 *   redirect/webhook en boucle complète depuis un frontend en dev local.
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
                // Pas de "#" : Orange rejette ce caractère dans `reference`
                // avec un 400 "Invalid body field ... bad syntax", vérifié
                // en réel (2026-09-03).
                'reference' => "StreamMali {$payment->id}",
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
