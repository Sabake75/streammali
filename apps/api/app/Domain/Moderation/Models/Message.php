<?php

namespace App\Domain\Moderation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['creator_id', 'sender_id', 'body'])]
class Message extends Model
{
    /**
     * The creator this conversation with the moderation team belongs to —
     * the same on both a creator's own message and a moderator's reply.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Who actually wrote this message — the creator themselves, or whichever
     * moderator replied.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
