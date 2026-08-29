<?php

namespace App\Notifications;

use App\Domain\Video\Models\Video;
use Illuminate\Notifications\Notification;

/**
 * "Ta vidéo a été validée/refusée" — fired from the Filament "Valider"/
 * "Refuser" actions (VideosTable). One class for both, the video's own
 * `status` at the time of firing decides which — mirrors how SendMessage
 * already handles both directions of the creator↔modération messaging
 * with a single action rather than two near-identical classes.
 */
class VideoStatusChanged extends Notification
{
    public function __construct(private readonly Video $video) {}

    /** @return array<int, string> */
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'video_status_changed',
            'video_id' => $this->video->id,
            'video_title' => $this->video->title,
            'status' => $this->video->status->value,
            'rejection_reason' => $this->video->rejection_reason,
        ];
    }
}
