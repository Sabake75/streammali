<?php

namespace App\Domain\Video\Models;

use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Video\Enums\VideoCategory;
use App\Models\User;
use Database\Factories\VideoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'creator_id',
    'title',
    'description',
    'category',
    'poster_path',
    'duration_seconds',
    'price',
    'status',
    'rejection_reason',
])]
class Video extends Model
{
    /** @use HasFactory<VideoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'category' => VideoCategory::class,
            'status' => VideoStatus::class,
            'duration_seconds' => 'integer',
            'price' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', VideoStatus::Approved);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    protected static function newFactory(): VideoFactory
    {
        return VideoFactory::new();
    }
}
