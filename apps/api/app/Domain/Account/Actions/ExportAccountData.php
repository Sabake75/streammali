<?php

namespace App\Domain\Account\Actions;

use App\Domain\Moderation\Models\Message;
use App\Domain\Payment\Models\LedgerEntry;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Models\Payout;
use App\Domain\Review\Models\Review;
use App\Domain\Viewer\Models\Favorite;
use App\Enums\UserRole;
use App\Models\User;

/**
 * Everything StreamMali holds about this specific user, as a plain array
 * ready to be returned as a JSON download — the "export my data"
 * self-service counterpart to DeleteAccount.
 */
class ExportAccountData
{
    public function __invoke(User $user): array
    {
        $data = [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role->value,
                'account_status' => $user->account_status->value,
                'created_at' => $user->created_at,
            ],
            'purchases' => Payment::where('buyer_id', $user->id)
                ->with('video:id,title')
                ->get()
                ->map(fn (Payment $payment) => [
                    'video_title' => $payment->video?->title,
                    'amount' => $payment->amount,
                    'status' => $payment->status->value,
                    'confirmed_at' => $payment->confirmed_at,
                ]),
            'favorites' => Favorite::where('user_id', $user->id)
                ->with('video:id,title')
                ->get()
                ->map(fn (Favorite $favorite) => $favorite->video?->title)
                ->filter()
                ->values(),
            'reviews' => Review::where('user_id', $user->id)
                ->with('video:id,title')
                ->get()
                ->map(fn (Review $review) => [
                    'video_title' => $review->video?->title,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                ]),
        ];

        // Creator-only sections — nothing to export here for a viewer.
        if ($user->role === UserRole::Creator) {
            $data['videos'] = $user->videos()
                ->get(['title', 'status', 'price', 'created_at'])
                ->map(fn ($video) => [
                    'title' => $video->title,
                    'status' => $video->status->value,
                    'price' => $video->price,
                    'created_at' => $video->created_at,
                ]);

            $data['ledger_entries'] = LedgerEntry::where('creator_id', $user->id)
                ->get(['gross_amount', 'commission_amount', 'net_amount', 'created_at'])
                ->map(fn (LedgerEntry $entry) => [
                    'gross_amount' => $entry->gross_amount,
                    'commission_amount' => $entry->commission_amount,
                    'net_amount' => $entry->net_amount,
                    'created_at' => $entry->created_at,
                ]);

            $data['payouts'] = Payout::where('creator_id', $user->id)
                ->get(['amount', 'status', 'created_at'])
                ->map(fn (Payout $payout) => [
                    'amount' => $payout->amount,
                    'status' => $payout->status->value,
                    'created_at' => $payout->created_at,
                ]);

            $data['messages'] = Message::where('creator_id', $user->id)
                ->with('sender:id,name')
                ->get()
                ->map(fn (Message $message) => [
                    'sender' => $message->sender?->name,
                    'body' => $message->body,
                    'created_at' => $message->created_at,
                ]);
        }

        return $data;
    }
}
