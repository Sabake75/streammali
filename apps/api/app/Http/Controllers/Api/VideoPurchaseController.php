<?php

namespace App\Http\Controllers\Api;

use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Payment\Actions\InitiatePayment;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Video\Models\Video;
use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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

    /**
     * "Mes achats" — every video the authenticated user has successfully
     * paid for, most recently purchased first. Ordered by the payment's
     * `confirmed_at` rather than the video's own `latest()` (creation
     * date): those two dates are unrelated, and a library sorted by video
     * creation date would put an old film someone just bought today
     * anywhere in the list instead of at the top.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $videos = Video::query()
            ->approved()
            ->with(['creator', 'category'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->whereHas('payments', fn ($query) => $query
                ->where('buyer_id', $request->user()->id)
                ->where('status', PaymentStatus::Succeeded))
            ->withMax(['payments as purchased_at' => fn ($query) => $query
                ->where('buyer_id', $request->user()->id)
                ->where('status', PaymentStatus::Succeeded)], 'confirmed_at')
            ->orderByDesc('purchased_at')
            ->paginate(15);

        return VideoResource::collection($videos);
    }
}
