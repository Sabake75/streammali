<?php

namespace App\Http\Controllers\Api\Creator;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * A creator's own sales history — one row per LedgerEntry (one per
     * successful purchase of one of their videos), distinct from
     * /creator/payouts which lists withdrawal requests, not individual
     * sales. Same underlying table the moderator sees in full on
     * /moderation/ledger-entries, scoped to the authenticated creator.
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()->role === UserRole::Creator,
            403,
            'Seuls les créateurs peuvent consulter leur historique de transactions.',
        );

        $entries = $request->user()->ledgerEntries()
            ->with('payment.video')
            ->latest()
            ->paginate(15);

        return response()->json($entries->through(fn ($entry) => [
            'id' => $entry->id,
            'video_title' => $entry->payment?->video?->title,
            'gross_amount' => $entry->gross_amount,
            'commission_amount' => $entry->commission_amount,
            'net_amount' => $entry->net_amount,
            'status' => $entry->payment ? [
                'value' => $entry->payment->status->value,
                'label' => $entry->payment->status->label(),
            ] : null,
            'created_at' => $entry->created_at,
        ]));
    }
}
