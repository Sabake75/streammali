<?php

namespace App\Http\Controllers\Api;

use App\Domain\Payment\Actions\ConfirmPayment;
use App\Domain\Payment\Models\Payment;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayDunyaWebhookController extends Controller
{
    public function __invoke(Request $request, ConfirmPayment $confirmPayment): JsonResponse
    {
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
