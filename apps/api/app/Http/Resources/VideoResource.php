<?php

namespace App\Http\Resources;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Video\Enums\VideoSourceStatus;
use App\Models\User;
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
        $purchased = $user !== null && $this->isPurchasedBy($user);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => [
                'value' => $this->category->value,
                'label' => $this->category->label(),
            ],
            'poster_path' => $this->poster_path,
            'duration_seconds' => $this->duration_seconds,
            'price' => $this->price,
            'creator' => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ],
            'purchased' => $this->when($user !== null, $purchased),
            // Only unlocked once bought and actually ready to stream —
            // "déverrouillage immédiat" from the cahier des charges.
            'playback_url' => $this->when(
                $purchased && $this->resource->source_status === VideoSourceStatus::Ready,
                $this->playback_url,
            ),
            'created_at' => $this->created_at,
        ];
    }

    private function isPurchasedBy(User $user): bool
    {
        return $this->resource->payments()
            ->where('buyer_id', $user->id)
            ->where('status', PaymentStatus::Succeeded)
            ->exists();
    }
}
