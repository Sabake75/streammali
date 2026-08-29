<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Moderation\Models\Message;
use App\Models\User;
use App\Notifications\NewModeratorMessage;

class SendMessage
{
    public function __invoke(User $creator, User $sender, string $body): Message
    {
        $message = Message::create([
            'creator_id' => $creator->id,
            'sender_id' => $sender->id,
            'body' => $body,
        ]);

        // Only the creator's own outgoing messages call this with
        // $creator === $sender (see MessageController::store) — anything
        // else is a moderator reply, worth notifying the creator about.
        if ($sender->id !== $creator->id) {
            $creator->notify(new NewModeratorMessage($message));
        }

        return $message;
    }
}
