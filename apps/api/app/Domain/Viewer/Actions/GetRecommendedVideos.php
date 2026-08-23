<?php

namespace App\Domain\Viewer\Actions;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Video\Models\Video;
use App\Domain\Viewer\Models\Favorite;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class GetRecommendedVideos
{
    /**
     * Deliberately not ML — approved videos in categories the user has
     * already bought or favorited, excluding videos they already own,
     * ordered by popularity. Guests (and users with no purchase/favorite
     * history yet) just get the most-viewed approved videos.
     */
    public function __invoke(?User $user, int $limit = 12): Collection
    {
        $query = Video::query()
            ->approved()
            ->with(['creator', 'category'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        if ($user !== null) {
            $ownedVideoIds = Payment::query()
                ->where('buyer_id', $user->id)
                ->where('status', PaymentStatus::Succeeded)
                ->pluck('video_id');

            $favoritedVideoIds = Favorite::query()
                ->where('user_id', $user->id)
                ->pluck('video_id');

            $categoryIds = Video::query()
                ->whereIn('id', $ownedVideoIds->merge($favoritedVideoIds))
                ->pluck('category_id')
                ->unique();

            if ($categoryIds->isNotEmpty()) {
                $query->whereIn('category_id', $categoryIds);
            }

            $query->whereNotIn('id', $ownedVideoIds);
        }

        return $query->orderByDesc('views_count')->limit($limit)->get();
    }
}
