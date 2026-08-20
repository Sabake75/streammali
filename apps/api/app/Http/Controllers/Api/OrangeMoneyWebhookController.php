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
        $payment = Payment::where('order_reference', $request->input('order_id'))->firstOrFail();

        $payment->update(['raw_webhook_payload' => $request->all()]);

        // The notif ping itself isn't trusted — ConfirmPayment re-verifies
        // the status directly with Orange before changing anything.
        $confirmPayment($payment);

        return response()->json(['status' => 'ok']);
    }
}
