<?php

namespace App\Http\Controllers\Api;

use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Payment\Actions\InitiatePayment;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Video\Models\Video;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoPurchaseController extends Controller
{
    public function store(Request $request, Video $video, InitiatePayment $initiatePayment): JsonResponse
    {
        abort_unless($video->status === VideoStatus::Approved, 404);

        $validated = $request->validate([
            'payer_msisdn' => ['required', 'string', 'max:32'],
        ]);

        $alreadyPurchased = $video->payments()
            ->where('buyer_id', $request->user()->id)
            ->where('status', PaymentStatus::Succeeded)
            ->exists();

        if ($alreadyPurchased) {
            return response()->json([
                'message' => 'Cette vidéo a déjà été achetée.',
            ], 409);
        }

        $result = $initiatePayment($request->user(), $video, $validated['payer_msisdn']);

        return response()->json([
            'payment' => [
                'id' => $result->payment->id,
                'order_reference' => $result->payment->order_reference,
                'status' => $result->payment->status->value,
                'amount' => $result->payment->amount,
            ],
            'payment_url' => $result->paymentUrl,
        ], 201);
    }
}
