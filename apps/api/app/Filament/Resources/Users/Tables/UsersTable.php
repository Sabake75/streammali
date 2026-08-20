<?php

namespace App\Filament\Resources\Users\Tables;

use App\Domain\Moderation\Enums\AccountStatus;
use App\Enums\UserRole;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Rôle')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state) => match ($state) {
                        UserRole::Creator => 'Créateur',
                        UserRole::Viewer => 'Viewer',
                        UserRole::Moderator => 'Modérateur',
                    }),
                TextColumn::make('account_status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (AccountStatus $state) => $state->color())
                    ->formatStateUsing(fn (AccountStatus $state) => $state->label()),
                IconColumn::make('identity_verified_at')
                    ->label('Identité vérifiée')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->identity_verified_at !== null),
                TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rôle')
                    ->options([
                        UserRole::Creator->value => 'Créateur',
                        UserRole::Viewer->value => 'Viewer',
                    ]),
                SelectFilter::make('account_status')
                    ->label('Statut')
                    ->options(collect(AccountStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])),
                TernaryFilter::make('identity_verified')
                    ->label('Identité vérifiée')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('identity_verified_at'),
                        false: fn ($query) => $query->whereNull('identity_verified_at'),
                    ),
            ])
            ->recordActions([
                Action::make('suspend')
                    ->label('Suspendre')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->visible(fn ($record) => $record->account_status !== AccountStatus::Suspended)
                    ->schema([
                        Textarea::make('reason')
                            ->label('Motif')
                            ->required(),
                    ])
                    ->action(fn ($record, array $data) => $record->update([
                        'account_status' => AccountStatus::Suspended,
                        'account_status_reason' => $data['reason'],
                    ])),
                Action::make('block')
                    ->label('Bloquer')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn ($record) => $record->account_status !== AccountStatus::Blocked)
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('reason')
                            ->label('Motif')
                            ->required(),
                    ])
                    ->action(fn ($record, array $data) => $record->update([
                        'account_status' => AccountStatus::Blocked,
                        'account_status_reason' => $data['reason'],
                    ])),
                Action::make('reactivate')
                    ->label('Réactiver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->account_status !== AccountStatus::Active)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update([
                        'account_status' => AccountStatus::Active,
                        'account_status_reason' => null,
                    ])),
                Action::make('verify_identity')
                    ->label(fn ($record) => $record->identity_verified_at ? "Annuler la vérification" : "Vérifier l'identité")
                    ->icon('heroicon-o-identification')
                    ->color('gray')
                    ->action(fn ($record) => $record->update([
                        'identity_verified_at' => $record->identity_verified_at ? null : now(),
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
