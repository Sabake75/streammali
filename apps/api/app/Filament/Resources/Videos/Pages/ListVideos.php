<?php

namespace App\Filament\Resources\Videos\Pages;

use App\Domain\Moderation\Enums\ReportStatus;
use App\Domain\Moderation\Enums\VideoStatus;
use App\Filament\Resources\Videos\VideoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListVideos extends ListRecords
{
    protected static string $resource = VideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * "Toutes" stays the default (first key, not overridden via
     * getDefaultActiveTab): a reported video is normally already Approved
     * (it went public, then got flagged), so defaulting to a pending-only
     * view would hide exactly the videos that most need attention.
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Toutes'),
            'pending' => Tab::make('En attente')
                ->modifyQueryUsing(fn ($query) => $query->where('status', VideoStatus::Pending))
                ->badge(fn () => VideoResource::getEloquentQuery()->where('status', VideoStatus::Pending)->count()),
            'reported' => Tab::make('Signalées')
                ->modifyQueryUsing(fn ($query) => $query->whereHas(
                    'reports',
                    fn ($query) => $query->where('status', ReportStatus::Pending),
                ))
                ->badge(fn () => VideoResource::getEloquentQuery()->whereHas(
                    'reports',
                    fn ($query) => $query->where('status', ReportStatus::Pending),
                )->count()),
            'approved' => Tab::make('Validées')
                ->modifyQueryUsing(fn ($query) => $query->where('status', VideoStatus::Approved)),
            'rejected' => Tab::make('Refusées')
                ->modifyQueryUsing(fn ($query) => $query->where('status', VideoStatus::Rejected)),
        ];
    }
}
