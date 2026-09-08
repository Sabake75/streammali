<?php

namespace App\Domain\Moderation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'sender_id', 'body'])]
class Message extends Model
{
    /**
     * Whose conversation with the moderation team this is (a creator or a
     * viewer — see App\Domain\Moderation\Actions\SendMessage) — the same on
     * both that user's own message and a moderator's reply.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Who actually wrote this message — the thread owner themselves, or
     * whichever moderator replied.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
