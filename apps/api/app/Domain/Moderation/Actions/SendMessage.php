<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Moderation\Models\Message;
use App\Models\User;

class SendMessage
{
    public function __invoke(User $creator, User $sender, string $body): Message
    {
        return Message::create([
            'creator_id' => $creator->id,
            'sender_id' => $sender->id,
            'body' => $body,
        ]);
    }
}
