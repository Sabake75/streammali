<?php

namespace App\Filament\Resources\LedgerEntries\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LedgerEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
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
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('creator_id')
                    ->label('Créateur')
                    ->relationship('creator', 'name'),
            ]);
    }
}
