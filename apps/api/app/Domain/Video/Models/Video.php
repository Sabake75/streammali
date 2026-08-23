<?php

namespace App\Domain\Video\Models;

use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Moderation\Models\Report;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Review\Models\Review;
use App\Domain\Video\Enums\VideoSourceStatus;
use App\Domain\Viewer\Models\Favorite;
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
    'category_id',
    'poster_path',
    'duration_seconds',
    'price',
    'status',
    'rejection_reason',
    'featured_at',
    'source_provider',
    'provider_video_id',
    'source_status',
    'playback_url',
    'preview_provider_video_id',
    'preview_playback_url',
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
            'status' => VideoStatus::class,
            'duration_seconds' => 'integer',
            'price' => 'integer',
            'featured_at' => 'datetime',
            'source_status' => VideoSourceStatus::class,
            'views_count' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', VideoStatus::Approved);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->whereNotNull('featured_at')->orderByDesc('featured_at');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Promoted from Http\Resources\VideoResource so review-gating (must
     * have bought the video to review it) can reuse the exact same check.
     */
    public function isPurchasedBy(User $user): bool
    {
        return $this->payments()
            ->where('buyer_id', $user->id)
            ->where('status', PaymentStatus::Succeeded)
            ->exists();
    }

    public function isFavoritedBy(User $user): bool
    {
        return $this->favorites()->where('user_id', $user->id)->exists();
    }

    protected static function newFactory(): VideoFactory
    {
        return VideoFactory::new();
    }
}
