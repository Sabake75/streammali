<?php

namespace App\Filament\Resources\Payouts\Schemas;

use App\Domain\Payment\Enums\PayoutStatus;
use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PayoutForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('creator_id')
                    ->label('Créateur')
                    ->relationship(
                        name: 'creator',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('role', UserRole::Creator),
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('amount')
                    ->label('Montant (FCFA)')
                    ->numeric()
                    ->required(),
                TextInput::make('destination_msisdn')
                    ->label('Numéro Mobile Money')
                    ->required(),
                Select::make('status')
                    ->label('Statut')
                    ->options(collect(PayoutStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                    ->required(),
                Textarea::make('rejection_reason')
                    ->label('Motif du rejet')
                    ->columnSpanFull(),
            ]);
    }
}
