<?php

namespace App\Filament\Resources\LedgerEntries\Tables;

use App\Domain\Payment\Enums\PaymentStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LedgerEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Créateur')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment.video.title')
                    ->label('Vidéo'),
                TextColumn::make('gross_amount')
                    ->label('Montant brut')
                    ->suffix(' FCFA'),
                TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->suffix(' FCFA'),
                TextColumn::make('net_amount')
                    ->label('Net créateur')
                    ->suffix(' FCFA'),
                TextColumn::make('payment.status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (?PaymentStatus $state) => $state?->color())
                    ->formatStateUsing(fn (?PaymentStatus $state) => $state?->label() ?? '—'),
                // The invoice token PayDunya assigns at checkout-invoice/create
                // stays the transaction's identifier for its whole lifecycle in
                // their API — there's no separate "transaction id" distinct
                // from it in the responses seen so far (compte sandbox bloqué
                // par le KYC, jamais vu une confirmation réelle — à revérifier
                // le jour où un vrai paiement aboutit).
                TextColumn::make('payment.provider_pay_token')
                    ->label('Référence PayDunya')
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('Référence copiée')
                    ->fontFamily('mono'),
                TextColumn::make('created_at')
                    ->label('Date et heure')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('creator_id')
                    ->label('Créateur')
                    ->relationship('creator', 'name'),
                SelectFilter::make('payment_status')
                    ->label('Statut')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                    ->query(fn ($query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($query, $value) => $query->whereHas('payment', fn ($query) => $query->where('status', $value)),
                    )),
            ]);
    }
}
