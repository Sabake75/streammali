<?php

namespace App\Domain\Creator\Actions;

use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Video\Models\Category;
use App\Domain\Video\Models\Video;
use App\Models\User;

class UploadVideo
{
    /**
     * @param array{title: string, description?: ?string, category: string, poster_path?: ?string, duration_seconds?: ?int, price?: ?int} $data
     */
    public function __invoke(User $creator, array $data): Video
    {
        // `category` is validated as an existing slug (exists:categories,slug)
        // before reaching this action — see Http\Controllers\Api\Creator\VideoController.
        $categoryId = Category::where('slug', $data['category'])->value('id');

        return Video::create([
            'creator_id' => $creator->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category_id' => $categoryId,
            'poster_path' => $data['poster_path'] ?? null,
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'price' => $data['price'] ?? 100,
            'status' => VideoStatus::Pending,
        ]);
    }
}
