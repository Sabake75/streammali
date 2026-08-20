<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Video\Models\Video
 */
class CreatorVideoResource extends JsonResource
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
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'rejection_reason' => $this->rejection_reason,
            'source_status' => [
                'value' => $this->source_status->value,
                'label' => $this->source_status->label(),
            ],
            'created_at' => $this->created_at,
        ];
    }
}
