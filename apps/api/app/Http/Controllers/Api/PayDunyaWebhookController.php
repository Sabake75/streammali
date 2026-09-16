<?php

namespace App\Http\Controllers\Api;

use App\Domain\Payment\Actions\ConfirmPayment;
use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Gateways\PayDunyaGateway;
use App\Domain\Payment\Models\Payment;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayDunyaWebhookController extends Controller
{
    public function __invoke(Request $request, ConfirmPayment $confirmPayment, PaymentGateway $gateway): JsonResponse
    {
        // This route stays mounted while PayDunya sits behind
        // OrangeMoneyGateway as the active binding (see CLAUDE.md) — reject
        // outright rather than let it look up a Payment by the guessable
        // `order_reference` (unlike Orange's webhook, which authenticates
        // via an unguessable provider_notif_token). ConfirmPayment
        // re-verifies with whichever gateway is actually bound before
        // trusting any webhook payload either way, so this isn't the only
        // safety net — but there's no reason to let this endpoint touch
        // Payment rows, or call out to a provider, while it's inactive.
        abort_unless($gateway instanceof PayDunyaGateway, 404);

        // PayDunya's IPN posts a single `data` field containing the invoice
        // as a JSON string, rather than top-level fields — same reasoning
        // as the Orange Money webhook applies to whatever shape this turns
        // out to be once verified against a real account: the payload is
        // only used to locate the Payment, never trusted for its status.
        $data = json_decode($request->input('data', '{}'), true) ?? [];
        $orderReference = $data['custom_data']['order_reference'] ?? $request->input('custom_data.order_reference');

        $payment = Payment::where('order_reference', $orderReference)->firstOrFail();

        $payment->update(['raw_webhook_payload' => $request->all()]);

        $confirmPayment($payment);

        return response()->json(['status' => 'ok']);
    }
}
