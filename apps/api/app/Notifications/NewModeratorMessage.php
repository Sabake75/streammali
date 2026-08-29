<?php

namespace App\Notifications;

use App\Domain\Moderation\Models\Message;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * "La modération t'a répondu" — fired from SendMessage::__invoke, the one
 * choke point both the creator's own outgoing messages and a moderator's
 * reply already flow through (see that class for the sender/creator
 * comparison that decides whether this fires).
 */
class NewModeratorMessage extends Notification
{
    public function __construct(private readonly Message $message) {}

    /** @return array<int, string> */
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'new_moderator_message',
            'message_id' => $this->message->id,
            'excerpt' => Str::limit($this->message->body, 120),
        ];
    }
}
