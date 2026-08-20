<?php

namespace App\Filament\Resources\Videos\Tables;

use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Video\Enums\VideoCategory;
use App\Domain\Video\Enums\VideoSourceStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VideosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Créateur')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Catégorie')
                    ->badge()
                    ->formatStateUsing(fn (VideoCategory $state) => $state->label()),
                TextColumn::make('price')
                    ->label('Prix')
                    ->suffix(' FCFA'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (VideoStatus $state) => $state->color())
                    ->formatStateUsing(fn (VideoStatus $state) => $state->label()),
                TextColumn::make('source_status')
                    ->label('Fichier vidéo')
                    ->badge()
                    ->color(fn (VideoSourceStatus $state) => $state->color())
                    ->formatStateUsing(fn (VideoSourceStatus $state) => $state->label()),
                TextColumn::make('created_at')
                    ->label('Soumis le')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'asc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(VideoStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])),
                SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options(collect(VideoCategory::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== VideoStatus::Approved)
                    // Rien à valider tant qu'il n'y a pas de fichier prêt à visionner.
                    ->disabled(fn ($record) => $record->source_status !== VideoSourceStatus::Ready)
                    ->tooltip(fn ($record) => $record->source_status !== VideoSourceStatus::Ready
                        ? "Le fichier vidéo n'est pas encore prêt."
                        : null)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update([
                        'status' => VideoStatus::Approved,
                        'rejection_reason' => null,
                    ])),
                Action::make('reject')
                    ->label('Refuser')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status !== VideoStatus::Rejected)
                    ->schema([
                        Textarea::make('rejection_reason')
                            ->label('Motif du refus')
                            ->required(),
                    ])
                    ->action(fn ($record, array $data) => $record->update([
                        'status' => VideoStatus::Rejected,
                        'rejection_reason' => $data['rejection_reason'],
                    ])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
