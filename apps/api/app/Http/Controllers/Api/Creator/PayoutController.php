<?php

namespace App\Http\Controllers\Api\Creator;

use App\Domain\Payment\Actions\GetCreatorBalance;
use App\Domain\Payment\Actions\RequestPayout;
use App\Domain\Payment\Exceptions\PayoutException;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function balance(Request $request, GetCreatorBalance $getCreatorBalance): JsonResponse
    {
        $this->authorizeCreator($request);

        return response()->json([
            'available_balance' => ($getCreatorBalance)($request->user()),
            'minimum_payout_amount' => config('platform.minimum_payout_amount'),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeCreator($request);

        $payouts = $request->user()->payouts()->latest()->paginate(15);

        return response()->json($payouts->through(fn ($payout) => [
            'id' => $payout->id,
            'amount' => $payout->amount,
            'destination_msisdn' => $payout->destination_msisdn,
            'status' => [
                'value' => $payout->status->value,
                'label' => $payout->status->label(),
            ],
            'rejection_reason' => $payout->rejection_reason,
            'processed_at' => $payout->processed_at,
            'created_at' => $payout->created_at,
        ]));
    }

    public function store(Request $request, RequestPayout $requestPayout): JsonResponse
    {
        $this->authorizeCreator($request);

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'destination_msisdn' => ['required', 'string', 'max:32'],
        ]);

        try {
            $payout = $requestPayout($request->user(), $validated['amount'], $validated['destination_msisdn']);
        } catch (PayoutException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'id' => $payout->id,
            'amount' => $payout->amount,
            'destination_msisdn' => $payout->destination_msisdn,
            'status' => $payout->status->value,
        ], 201);
    }

    private function authorizeCreator(Request $request): void
    {
        abort_unless(
            $request->user()->role === UserRole::Creator,
            403,
            'Seuls les créateurs peuvent gérer des demandes de retrait.',
        );
    }
}
