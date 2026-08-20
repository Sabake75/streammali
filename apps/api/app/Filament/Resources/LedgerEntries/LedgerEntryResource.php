<?php

namespace App\Filament\Resources\LedgerEntries;

use App\Domain\Payment\Models\LedgerEntry;
use App\Filament\Resources\LedgerEntries\Pages\ListLedgerEntries;
use App\Filament\Resources\LedgerEntries\Tables\LedgerEntriesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;

/**
 * Read-only: entries are only ever created by ConfirmPayment when a
 * payment succeeds, never edited by hand.
 */
class LedgerEntryResource extends Resource
{
    protected static ?string $model = LedgerEntry::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $modelLabel = 'transaction';

    protected static ?string $pluralModelLabel = 'transactions';

    public static function table(Table $table): Table
    {
        return LedgerEntriesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLedgerEntries::route('/'),
        ];
    }
}
