<?php

namespace App\Filament\Resources\Videos\Pages;

use App\Filament\Resources\Videos\VideoResource;
use Filament\Resources\Pages\EditRecord;

/**
 * No DeleteAction — a moderator's tool for unpublishing content is
 * "Refuser" (VideosTable), which sets status=rejected without touching
 * anything else. A hard delete here cascades onto payments.video_id
 * (cascadeOnDelete in the migration), permanently destroying real
 * purchase/payment records for anyone who already bought the video —
 * never an outcome a moderation action should produce.
 */
class EditVideo extends EditRecord
{
    protected static string $resource = VideoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
