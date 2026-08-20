<?php

namespace App\Domain\Moderation\Models;

use App\Domain\Moderation\Enums\ReportStatus;
use App\Domain\Video\Models\Video;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['video_id', 'reporter_id', 'reason', 'status'])]
class Report extends Model
{
    /**
     * Eloquent doesn't hydrate DB column defaults into memory after
     * create() — see the same fix on App\Models\User.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
        ];
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
