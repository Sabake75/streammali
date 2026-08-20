<?php

namespace App\Domain\Creator\Actions;

use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Video\Models\Video;
use App\Models\User;

class UploadVideo
{
    /**
     * @param array{title: string, description?: ?string, category: string, poster_path?: ?string, duration_seconds?: ?int, price?: ?int} $data
     */
    public function __invoke(User $creator, array $data): Video
    {
        return Video::create([
            'creator_id' => $creator->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'],
            'poster_path' => $data['poster_path'] ?? null,
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'price' => $data['price'] ?? 25,
            'status' => VideoStatus::Pending,
        ]);
    }
}
