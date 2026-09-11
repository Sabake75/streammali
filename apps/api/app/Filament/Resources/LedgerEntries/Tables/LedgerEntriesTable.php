<?php

namespace App\Filament\Resources\LedgerEntries\Tables;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Filament\Exports\LedgerEntryExporter;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class LedgerEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()
                    ->label('Exporter en Excel')
                    ->exporter(LedgerEntryExporter::class),
            ])
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
                Filter::make('id')
                    ->label('ID')
                    ->schema([
                        TextInput::make('min')->label('Min')->numeric(),
                        TextInput::make('max')->label('Max')->numeric(),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['min'] ?? null, fn ($query, $value) => $query->where('id', '>=', $value))
                        ->when($data['max'] ?? null, fn ($query, $value) => $query->where('id', '<=', $value)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['min'] ?? null) $indicators[] = 'ID ≥ '.$data['min'];
                        if ($data['max'] ?? null) $indicators[] = 'ID ≤ '.$data['max'];

                        return $indicators;
                    }),
                SelectFilter::make('creator_id')
                    ->label('Créateur')
                    ->relationship('creator', 'name'),
                Filter::make('video')
                    ->label('Vidéo')
                    ->schema([
                        TextInput::make('title')->label('Titre contient'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['title'] ?? null,
                        fn ($query, $value) => $query->whereHas(
                            'payment.video',
                            fn ($query) => $query->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($value).'%']),
                        ),
                    ))
                    ->indicateUsing(fn (array $data) => ($data['title'] ?? null) ? ['Vidéo : "'.$data['title'].'"'] : []),
                Filter::make('gross_amount')
                    ->label('Montant brut')
                    ->schema([
                        TextInput::make('min')->label('Min (FCFA)')->numeric(),
                        TextInput::make('max')->label('Max (FCFA)')->numeric(),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['min'] ?? null, fn ($query, $value) => $query->where('gross_amount', '>=', $value))
                        ->when($data['max'] ?? null, fn ($query, $value) => $query->where('gross_amount', '<=', $value)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['min'] ?? null) $indicators[] = 'Brut ≥ '.$data['min'].' FCFA';
                        if ($data['max'] ?? null) $indicators[] = 'Brut ≤ '.$data['max'].' FCFA';

                        return $indicators;
                    }),
                Filter::make('commission_amount')
                    ->label('Commission')
                    ->schema([
                        TextInput::make('min')->label('Min (FCFA)')->numeric(),
                        TextInput::make('max')->label('Max (FCFA)')->numeric(),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['min'] ?? null, fn ($query, $value) => $query->where('commission_amount', '>=', $value))
                        ->when($data['max'] ?? null, fn ($query, $value) => $query->where('commission_amount', '<=', $value)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['min'] ?? null) $indicators[] = 'Commission ≥ '.$data['min'].' FCFA';
                        if ($data['max'] ?? null) $indicators[] = 'Commission ≤ '.$data['max'].' FCFA';

                        return $indicators;
                    }),
                Filter::make('net_amount')
                    ->label('Net créateur')
                    ->schema([
                        TextInput::make('min')->label('Min (FCFA)')->numeric(),
                        TextInput::make('max')->label('Max (FCFA)')->numeric(),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['min'] ?? null, fn ($query, $value) => $query->where('net_amount', '>=', $value))
                        ->when($data['max'] ?? null, fn ($query, $value) => $query->where('net_amount', '<=', $value)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['min'] ?? null) $indicators[] = 'Net ≥ '.$data['min'].' FCFA';
                        if ($data['max'] ?? null) $indicators[] = 'Net ≤ '.$data['max'].' FCFA';

                        return $indicators;
                    }),
                SelectFilter::make('payment_status')
                    ->label('Statut')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                    ->query(fn ($query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($query, $value) => $query->whereHas('payment', fn ($query) => $query->where('status', $value)),
                    )),
                Filter::make('provider_transaction_id')
                    ->label('ID transaction')
                    ->schema([
                        TextInput::make('value')->label('Contient'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($query, $value) => $query->whereHas(
                            'payment',
                            fn ($query) => $query->where('provider_transaction_id', 'like', '%'.$value.'%'),
                        ),
                    ))
                    ->indicateUsing(fn (array $data) => ($data['value'] ?? null) ? ['ID transaction : "'.$data['value'].'"'] : []),
                Filter::make('created_at')
                    ->label('Date et heure')
                    ->schema([
                        DatePicker::make('from')->label('Du'),
                        DatePicker::make('until')->label('Au'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($query, $value) => $query->whereDate('created_at', '>=', $value))
                        ->when($data['until'] ?? null, fn ($query, $value) => $query->whereDate('created_at', '<=', $value)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) $indicators[] = 'Depuis le '.Carbon::parse($data['from'])->format('d/m/Y');
                        if ($data['until'] ?? null) $indicators[] = "Jusqu'au ".Carbon::parse($data['until'])->format('d/m/Y');

                        return $indicators;
                    }),
            ]);
    }
}
