<?php

namespace App\Filament\Resources\Payouts\Tables;

use App\Domain\Payment\Enums\PayoutStatus;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PayoutsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Même bug/correctif que UsersTable/VideosTable : sans ça,
            // cliquer sur "Numéro Mobile Money" ou une autre cellule ouvre
            // le formulaire d'édition au lieu de rien faire.
            ->recordUrl(null)
            ->columns([
                TextColumn::make('creator.name')
                    ->label('Créateur')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Montant')
                    ->suffix(' FCFA')
                    ->sortable(),
                TextColumn::make('destination_msisdn')
                    ->label('Numéro Mobile Money'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (PayoutStatus $state) => $state->color())
                    ->formatStateUsing(fn (PayoutStatus $state) => $state->label()),
                TextColumn::make('created_at')
                    ->label('Demandé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'asc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(PayoutStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])),
            ])
            ->recordActions([
                Action::make('mark_paid')
                    ->label('Marquer payé')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === PayoutStatus::Pending)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update([
                        'status' => PayoutStatus::Paid,
                        'processed_at' => now(),
                    ])),
                Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === PayoutStatus::Pending)
                    ->schema([
                        Textarea::make('rejection_reason')
                            ->label('Motif du rejet')
                            ->required(),
                    ])
                    ->action(fn ($record, array $data) => $record->update([
                        'status' => PayoutStatus::Rejected,
                        'rejection_reason' => $data['rejection_reason'],
                        'processed_at' => now(),
                    ])),
                EditAction::make(),
            ]);
        // Pas de toolbarActions/DeleteBulkAction : un retrait supprimé par
        // erreur perd la trace d'une vraie demande d'argent réel — "Rejeter"
        // (déjà ci-dessus) est l'outil prévu pour un retrait qu'on refuse.
    }
}
