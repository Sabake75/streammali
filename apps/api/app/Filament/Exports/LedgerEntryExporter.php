<?php

namespace App\Filament\Exports;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\LedgerEntry;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class LedgerEntryExporter extends Exporter
{
    protected static ?string $model = LedgerEntry::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('created_at')->label('Date et heure'),
            ExportColumn::make('creator.name')->label('Créateur'),
            ExportColumn::make('payment.video.title')->label('Vidéo'),
            ExportColumn::make('gross_amount')->label('Montant brut (FCFA)'),
            ExportColumn::make('commission_amount')->label('Commission (FCFA)'),
            ExportColumn::make('net_amount')->label('Net créateur (FCFA)'),
            ExportColumn::make('payment.status')
                ->label('Statut')
                ->formatStateUsing(fn (?PaymentStatus $state) => $state?->label() ?? ''),
            ExportColumn::make('payment.provider_transaction_id')->label('ID transaction'),
        ];
    }

    // Pas de worker de queue en production (voir render.yaml — seulement
    // deux services "web", aucun "worker") : le volume de transactions
    // reste assez faible pour traiter l'export dans la requête elle-même
    // plutôt que d'ajouter un service Render payant dédié juste pour ça.
    public function getJobConnection(): ?string
    {
        return 'sync';
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = $export->successful_rows;
        $label = $count === 1 ? 'transaction' : 'transactions';

        return "L'export de {$count} {$label} est terminé.".($export->failed_rows ? " {$export->failed_rows} lignes en échec." : '');
    }
}
