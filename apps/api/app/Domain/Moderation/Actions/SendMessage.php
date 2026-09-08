<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Moderation\Models\Message;
use App\Models\User;
use App\Notifications\NewModeratorMessage;

class SendMessage
{
    public function __invoke(User $threadOwner, User $sender, string $body): Message
    {
        $message = Message::create([
            'user_id' => $threadOwner->id,
            'sender_id' => $sender->id,
            'body' => $body,
        ]);

        // Only the thread owner's own outgoing messages call this with
        // $threadOwner === $sender (see MessageController::store) — anything
        // else is a moderator reply, worth notifying them about.
        if ($sender->id !== $threadOwner->id) {
            $threadOwner->notify(new NewModeratorMessage($message));
        }

        return $message;
    }
}
