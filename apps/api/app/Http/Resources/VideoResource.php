<?php

namespace App\Http\Resources;

use App\Domain\Video\Enums\VideoSourceStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Video\Models\Video
 */
class VideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Explicit guard name: this resource is also used on public routes
        // with no auth:sanctum middleware, where $request->user() would
        // resolve against the default ('web') guard and always be null.
        $user = $request->user('sanctum');
        $purchased = $user !== null && $this->resource->isPurchasedBy($user);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => [
                'value' => $this->category->slug,
                'label' => $this->category->label,
            ],
            'poster_path' => $this->poster_path,
            'duration_seconds' => $this->duration_seconds,
            'price' => $this->price,
            'creator' => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ],
            'purchased' => $this->when($user !== null, $purchased),
            'favorited' => $this->when($user !== null, fn () => $this->resource->isFavoritedBy($user)),
            // Only unlocked once bought and actually ready to stream —
            // "déverrouillage immédiat" from the cahier des charges.
            'playback_url' => $this->when(
                $purchased && $this->resource->source_status === VideoSourceStatus::Ready,
                $this->playback_url,
            ),
            // Unlike playback_url, always exposed (even to guests) once
            // set — it's a short standalone clip, not the gated asset.
            'preview_playback_url' => $this->preview_playback_url,
            // reviews_avg_rating/reviews_count come from withAvg/withCount
            // (VideoCatalogController) — null/0 here just means the caller
            // didn't eager-load them, not that there truly are no reviews.
            'average_rating' => $this->reviews_avg_rating !== null
                ? round((float) $this->reviews_avg_rating, 1)
                : null,
            'reviews_count' => (int) ($this->reviews_count ?? 0),
            'created_at' => $this->created_at,
            // "Reçu" detail — only present when the caller eager-loaded a
            // `payments` relation constrained to this user's own successful
            // payment (VideoPurchaseController::index, "Mes achats"). Absent
            // everywhere else (catalogue, favorites, recommended…), which
            // never load `payments` this way.
            'purchase' => $this->when(
                $this->relationLoaded('payments') && $this->payments->isNotEmpty(),
                function () {
                    $payment = $this->payments->first();

                    return [
                        'amount' => $payment->amount,
                        'purchased_at' => $payment->confirmed_at,
                        'order_reference' => $payment->order_reference,
                    ];
                },
            ),
        ];
    }
}
