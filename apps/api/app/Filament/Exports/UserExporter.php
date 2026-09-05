<?php

namespace App\Filament\Exports;

use App\Domain\Moderation\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class UserExporter extends Exporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('name')->label('Nom'),
            ExportColumn::make('phone')->label('Téléphone'),
            ExportColumn::make('email')->label('Email'),
            ExportColumn::make('role')
                ->label('Rôle')
                ->formatStateUsing(fn (UserRole $state) => match ($state) {
                    UserRole::Creator => 'Créateur',
                    UserRole::Viewer => 'Viewer',
                    UserRole::Moderator => 'Modérateur',
                }),
            ExportColumn::make('account_status')
                ->label('Statut')
                ->formatStateUsing(fn (AccountStatus $state) => $state->label()),
            ExportColumn::make('identity_verified_at')->label("Identité vérifiée le"),
            ExportColumn::make('created_at')->label('Inscrit le'),
        ];
    }

    // Pas de worker de queue en production (voir render.yaml — seulement
    // deux services "web", aucun "worker") : le volume de comptes reste
    // assez faible pour traiter l'export dans la requête elle-même plutôt
    // que d'ajouter un service Render payant dédié juste pour ça.
    public function getJobConnection(): ?string
    {
        return 'sync';
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = $export->successful_rows;
        $label = $count === 1 ? 'compte' : 'comptes';

        return "L'export de {$count} {$label} est terminé.".($export->failed_rows ? " {$export->failed_rows} lignes en échec." : '');
    }
}
