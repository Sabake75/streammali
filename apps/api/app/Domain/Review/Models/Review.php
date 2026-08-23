<?php

namespace App\Domain\Review\Models;

use App\Domain\Video\Models\Video;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['video_id', 'user_id', 'rating', 'comment'])]
class Review extends Model
{
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
