<?php

namespace App\Http\Controllers\Api;

use App\Domain\Payment\Actions\ConfirmPayment;
use App\Domain\Payment\Models\Payment;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrangeMoneyWebhookController extends Controller
{
    public function __invoke(Request $request, ConfirmPayment $confirmPayment): JsonResponse
    {
        // Orange's notification body only carries {status, notif_token, txnid}
        // — no order_id — per their "Guide d'utilisation API webpayment".
        // notif_token is also how we check the notification is genuine: it's
        // the one we generated and stored ourselves at initiation, never
        // guessable from the outside (unlike order_reference).
        $payment = Payment::where('provider_notif_token', $request->input('notif_token'))->firstOrFail();

        $payment->update(['raw_webhook_payload' => $request->all()]);

        // The notif ping itself isn't trusted — ConfirmPayment re-verifies
        // the status directly with Orange before changing anything.
        $confirmPayment($payment);

        return response()->json(['status' => 'ok']);
    }
}
