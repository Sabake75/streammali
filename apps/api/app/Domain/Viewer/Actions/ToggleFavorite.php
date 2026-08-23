<?php

namespace App\Domain\Viewer\Actions;

use App\Domain\Video\Models\Video;
use App\Domain\Viewer\Models\Favorite;
use App\Models\User;

class ToggleFavorite
{
    /**
     * @return bool true if the video is now favorited, false if the
     *              toggle just removed it
     */
    public function __invoke(User $user, Video $video): bool
    {
        $favorite = Favorite::where('user_id', $user->id)->where('video_id', $video->id)->first();

        if ($favorite !== null) {
            $favorite->delete();

            return false;
        }

        Favorite::create(['user_id' => $user->id, 'video_id' => $video->id]);

        return true;
    }
}
