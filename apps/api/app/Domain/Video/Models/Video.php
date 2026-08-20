<?php

namespace App\Domain\Video\Models;

use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Moderation\Models\Report;
use App\Domain\Payment\Models\Payment;
use App\Domain\Video\Enums\VideoCategory;
use App\Domain\Video\Enums\VideoSourceStatus;
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
    'source_provider',
    'provider_video_id',
    'source_status',
    'playback_url',
])]
class Video extends Model
{
    /** @use HasFactory<VideoFactory> */
    use HasFactory;

    /**
     * Eloquent doesn't hydrate DB column defaults into memory after
     * create() — see the same fix on App\Models\User.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'source_status' => 'not_started',
        'views_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'category' => VideoCategory::class,
            'status' => VideoStatus::class,
            'duration_seconds' => 'integer',
            'price' => 'integer',
            'source_status' => VideoSourceStatus::class,
            'views_count' => 'integer',
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

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    protected static function newFactory(): VideoFactory
    {
        return VideoFactory::new();
    }
}
