<?php

namespace App\Http\Resources;

use App\Domain\Payment\Enums\PaymentStatus;
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
            'purchased' => $this->when(
                $request->user() !== null,
                fn () => $this->isPurchasedBy($request->user()),
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
