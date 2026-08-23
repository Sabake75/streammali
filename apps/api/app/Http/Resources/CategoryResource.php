<?php

namespace App\Http\Resources;

use App\Domain\Video\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->slug,
            'label' => $this->label,
        ];
    }
}
