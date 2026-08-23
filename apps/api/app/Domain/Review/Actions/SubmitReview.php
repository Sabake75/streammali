<?php

namespace App\Domain\Review\Actions;

use App\Domain\Review\Models\Review;
use App\Domain\Video\Models\Video;
use App\Models\User;

class SubmitReview
{
    /**
     * Resubmitting replaces the reviewer's existing review rather than
     * creating a duplicate — see the unique(video_id, user_id) constraint.
     */
    public function __invoke(Video $video, User $user, int $rating, ?string $comment): Review
    {
        return Review::updateOrCreate(
            ['video_id' => $video->id, 'user_id' => $user->id],
            ['rating' => $rating, 'comment' => $comment],
        );
    }
}
