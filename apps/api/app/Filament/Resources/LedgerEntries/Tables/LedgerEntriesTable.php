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
                // txnid tel que fourni par Orange Money dans son webhook de
                // confirmation (voir OrangeMoneyWebhookController) — distinct
                // du pay_token, qui ne sert qu'en interne à appeler leur API.
                // Vide pour un paiement PayDunya (jamais renseigné côté
                // PayDunyaWebhookController, forme de son IPN non vérifiée).
                TextColumn::make('payment.provider_transaction_id')
                    ->label('ID transaction')
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('ID copié')
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
